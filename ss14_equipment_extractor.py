#!/usr/bin/env python3
"""Extract searchable equipment prototypes from configured SS14 forks.

This intentionally stores source YAML components instead of pretending to
fully emulate RobustToolbox prototype inheritance. Parent ids are retained so
that inheritance can be resolved incrementally and the UI can distinguish
verified raw values from absent values.
"""
from __future__ import annotations
import argparse, json, os, re, sys, time, subprocess, urllib.error, urllib.parse, urllib.request
from pathlib import Path
from concurrent.futures import ThreadPoolExecutor, as_completed
import yaml

ROOT = Path(__file__).resolve().parent
CACHE = ROOT / 'cache' / 'equipment'
OUT = ROOT / 'storage' / 'app' / 'equipment-items.json'

class SS14Loader(yaml.SafeLoader):
    pass

def type_constructor(loader, suffix, node):
    if isinstance(node, yaml.MappingNode):
        value = loader.construct_mapping(node, deep=True); value['_type'] = suffix; return value
    if isinstance(node, yaml.SequenceNode):
        return {'_type': suffix, '_items': loader.construct_sequence(node, deep=True)}
    return {'_type': suffix, '_value': loader.construct_scalar(node)}
SS14Loader.add_multi_constructor('!type:', type_constructor)

KEYWORDS = re.compile(r'(clothing|weapon|gun|ammo|ammunition|grenade|explos|armor|armour|backpack|belt|storage|security|tactical|implant|shield|helmet|suit|medical|medkit|tool|melee|sword|mech|combat|equipment|handcuff|restraint|bullet|magazine|cartridge|bomb|ordnance|holster|flashbang|riot|space\s*marine)', re.I)
COMPONENT_HINTS = {
 'Clothing':'armor', 'Armor':'armor', 'Gun':'weapon', 'MeleeWeapon':'melee', 'BallisticAmmoProvider':'ammo',
 'AmmoCounter':'ammo', 'Cartridge':'ammo', 'Projectile':'ammo', 'ThrownItem':'grenade', 'Explosion':'explosive',
 'Explosive':'explosive', 'Storage':'storage', 'StorageFill':'storage', 'ItemSlots':'equipment', 'HandheldLight':'tool',
 'MedicalScanner':'medical', 'Medkit':'medical', 'Implant':'implant', 'Shield':'shield', 'Battery':'equipment',
 'Item':'equipment', 'Stack':'equipment', 'ArmorComponent':'armor', 'InventorySlots':'equipment'
}

def get_json(url, cache_path=None, headers=None):
    if cache_path and cache_path.exists():
        try: return json.loads(cache_path.read_text(encoding='utf-8'))
        except Exception: pass
    req = urllib.request.Request(url, headers={'User-Agent':'SS14-Tactical-DB/1.0', 'Accept':'application/vnd.github+json', **(headers or {})})
    with urllib.request.urlopen(req, timeout=45) as response: raw = response.read()
    if cache_path:
        cache_path.parent.mkdir(parents=True, exist_ok=True); cache_path.write_bytes(raw)
    return json.loads(raw.decode('utf-8'))

def get_text(url, cache_path):
    if cache_path.exists():
        try: return cache_path.read_text(encoding='utf-8')
        except Exception: pass
    req = urllib.request.Request(url, headers={'User-Agent':'SS14-Tactical-DB/1.0'})
    with urllib.request.urlopen(req, timeout=45) as response: raw = response.read()
    cache_path.parent.mkdir(parents=True, exist_ok=True); cache_path.write_bytes(raw)
    return raw.decode('utf-8', errors='replace')

def fork_registry():
    """Read the same PHP registry that the Laravel chemistry sync uses."""
    php = os.environ.get('SS14_PHP', 'php')
    config_path = ROOT / 'config' / 'game_sources.php'
    code = '$config = require $argv[1]; echo json_encode($config, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);'
    result = subprocess.run([php, '-r', code, str(config_path)], cwd=ROOT, capture_output=True, text=True, timeout=30)
    if result.returncode:
        raise RuntimeError(result.stderr.strip() or 'Не удалось прочитать config/game_sources.php через PHP')
    registry = json.loads(result.stdout)
    if not isinstance(registry, dict) or not registry:
        raise RuntimeError('config/game_sources.php пуст или повреждён')
    return registry

def relevant_path(path):
    if not path.startswith('Resources/Prototypes/') or not path.lower().endswith(('.yml', '.yaml')):
        return False
    lower = path.lower()
    # Keep likely equipment domains and shared base prototypes needed for labels.
    if '/entities/' not in lower:
        return False
    if KEYWORDS.search(lower): return True
    base_name = Path(lower).name
    return base_name.startswith('base_') and any(word in base_name for word in ('item','clothing','weapon','gun','armor','armour','storage','ammo','grenade','magazine'))

def flatten_components(value):
    if isinstance(value, list):
        for item in value:
            if isinstance(item, dict): yield item
    elif isinstance(value, dict):
        yield value

def infer_category(path, components):
    text = path.lower() + ' ' + ' '.join(str(c.get('_type') or c.get('type') or '') for c in components).lower()
    checks = [
      ('grenade','grenade'),('explosive','explosive'),('ordnance','explosive'),('ammunition','ammo'),('ammo','ammo'),('cartridge','ammo'),('magazine','ammo'),
      ('gun','weapon'),('weapon','weapon'),('rifle','weapon'),('pistol','weapon'),('melee','melee'),('sword','melee'),('clothing','armor'),('armor','armor'),('armour','armor'),('helmet','armor'),('hardsuit','armor'),('shield','shield'),('implant','implant'),('medical','medical'),('medkit','medical'),('backpack','storage'),('belt','storage'),('storage','storage'),('tool','tool'),('security','equipment'),('equipment','equipment'),('weapon','weapon')]
    for needle, category in checks:
        if needle in text: return category
    component_types = {str(c.get('_type') or c.get('type') or '') for c in components}
    for comp, category in COMPONENT_HINTS.items():
        if comp in component_types: return category
    return 'equipment'

def extract_proto(proto, fork_id, fork_name):
    if not isinstance(proto, dict) or proto.get('type') != 'entity' or not isinstance(proto.get('id'), str): return None
    proto_id = proto['id'].strip()
    if not proto_id: return None
    path = str(proto.get('_source_file',''))
    components = list(flatten_components(proto.get('components', [])))
    # Only keep records which look like a tangible item/equipment prototype.
    category = infer_category(path, components)
    parent = proto.get('parent', [])
    if isinstance(parent, str): parent = [parent]
    if not isinstance(parent, list): parent = []
    name = proto.get('name') if isinstance(proto.get('name'), str) else proto_id
    desc = proto.get('description') if isinstance(proto.get('description'), str) else None
    component_map = {}
    sprite = None
    slots = []
    tags = []
    for comp in components:
        ctype = str(comp.get('_type') or comp.get('type') or '')
        # A small normalized summary for fast filtering/comparison; preserve full raw data too.
        # Keep every component, not just a short allow-list: future/unfamiliar fork
        # components are still available to the universal human-readable presenter.
        if ctype:
            component_map[ctype] = comp
        if ctype == 'Sprite' and isinstance(comp.get('sprite'), str): sprite = comp.get('sprite')
        if ctype == 'Clothing':
            slots_value = comp.get('slots', [])
            if isinstance(slots_value, str): slots_value = [slots_value]
            if isinstance(slots_value, list): slots.extend(str(s) for s in slots_value)
        if ctype == 'Tag':
            tv = comp.get('tags', [])
            if isinstance(tv, list): tags.extend(str(t) for t in tv)
    # Split armor/clothing into player-facing equipment slots instead of one giant category.
    if category == 'armor':
        slot_text = ' '.join(slots).lower()
        id_text = (proto_id + ' ' + name + ' ' + path).lower()
        slot_rules = [
            (('head', 'helmet', 'голов'), 'armor_head'),
            (('eyes', 'eye', 'очки', 'глаз'), 'armor_eyes'),
            (('mask', 'маск'), 'armor_mask'),
            (('neck', 'шея'), 'armor_neck'),
            (('back', 'спина', 'рюкзак'), 'armor_back'),
            (('belt', 'waist', 'пояс', 'разгруз'), 'armor_belt'),
            (('gloves', 'hands', 'перчат'), 'armor_gloves'),
            (('feet', 'shoes', 'обув'), 'armor_shoes'),
            (('outerclothing', 'outer clothing', 'outer', 'пальто', 'куртк'), 'armor_outer'),
            (('jumpsuit', 'innerclothing', 'inner clothing', 'комбинезон', 'uniform', 'нагруд'), 'armor_clothing'),
            (('chest', 'torso', 'body', 'бронежилет', 'нагрудник'), 'armor_chest'),
        ]
        combined = slot_text + ' ' + id_text
        chosen = None
        # Prefer explicit clothing slot identifiers, then common prototype/name hints.
        for needles, subcategory in slot_rules:
            if any(needle in slot_text for needle in needles):
                chosen = subcategory
                break
        if chosen is None:
            for needles, subcategory in slot_rules:
                if any(needle in id_text for needle in needles):
                    chosen = subcategory
                    break
        category = chosen or 'armor_other'
    # Ignore generic non-item entity prototypes unless they have item/clothing/weapon traits.
    itemish = any(str(c.get('_type') or c.get('type') or '') in {'Item','Clothing','Gun','MeleeWeapon','Stack','Storage','BallisticAmmoProvider','Cartridge','Projectile','ThrownItem','ItemSlots','Armor','Shield','Implant'} for c in components)
    if not itemish and category == 'equipment' and not KEYWORDS.search(proto_id + ' ' + name): return None
    return {
      'source_fork': fork_id, 'source_name': fork_name, 'prototype_id': proto_id,
      'name': name[:255], 'description': desc[:5000] if desc else None,
      'category': category, 'prototype_type': proto.get('type','entity'),
      'parent_ids': [str(p) for p in parent if isinstance(p, (str,int))],
      'sprite_path': sprite, 'slots': sorted(set(slots)), 'tags': sorted(set(tags)),
      'components': component_map, 'source_file': path,
      'source_url': None
    }

def fetch_fork(fork_id, conf, max_files=0):
    repo = conf.get('repo'); branch = conf.get('branch') or 'master'; fork_name = conf.get('name') or fork_id
    if not repo: return [], {'fork':fork_id,'error':'missing repo'}
    safe = re.sub(r'[^a-zA-Z0-9_.-]+','_', fork_id)
    tree_file = CACHE / f'{safe}-tree.json'
    tree_url = f'https://api.github.com/repos/{repo}/git/trees/{urllib.parse.quote(branch, safe="")}?recursive=1'
    try:
        tree = get_json(tree_url, tree_file)
        paths = sorted({row['path'] for row in tree.get('tree', []) if row.get('type') == 'blob' and relevant_path(row.get('path',''))})
    except Exception as exc:
        return [], {'fork':fork_id,'error':f'tree: {exc}'}
    if max_files: paths = paths[:max_files]
    out, failures = [], 0
    def fetch_one(path):
        local = CACHE / safe / (path.replace('/','__') + '.txt')
        url = f'https://raw.githubusercontent.com/{repo}/{branch}/{path}'
        return path, get_text(url, local), url
    with ThreadPoolExecutor(max_workers=8) as pool:
        futures = {pool.submit(fetch_one,p):p for p in paths}
        done = 0
        for future in as_completed(futures):
            done += 1
            try:
                path, content, url = future.result()
                for doc in yaml.load_all(content, Loader=SS14Loader):
                    docs = doc if isinstance(doc, list) else [doc]
                    for proto in docs:
                        if not isinstance(proto, dict): continue
                        proto['_source_file'] = path
                        row = extract_proto(proto, fork_id, fork_name)
                        if row:
                            row['source_url'] = url
                            out.append(row)
            except Exception as exc:
                failures += 1
                if failures <= 3: print(f'  WARN {fork_id}: {futures[future]}: {exc}', file=sys.stderr)
            if done % 50 == 0: print(f'  {fork_name}: {done}/{len(paths)} files, {len(out)} item prototypes')
    # De-duplicate same prototype id within a fork, keep last definition in stable path order.
    by_id = {}
    for row in out: by_id[row['prototype_id']] = row
    result = resolve_inheritance(list(by_id.values()))
    return result, {'fork':fork_id,'name':fork_name,'files':len(paths),'items':len(result),'failed_files':failures,'truncated':bool(max_files and len(paths) >= max_files)}

def deep_merge(base, override):
    if not isinstance(base, dict) or not isinstance(override, dict): return override
    merged = dict(base)
    for key, value in override.items():
        merged[key] = deep_merge(merged[key], value) if key in merged and isinstance(merged[key], dict) and isinstance(value, dict) else value
    return merged

def resolve_inheritance(rows):
    """Resolve the prototype parent chain for the normalized component summary."""
    by_id = {row['prototype_id']: row for row in rows}
    resolved, visiting = {}, set()
    def visit(proto_id, depth=0):
        if proto_id in resolved: return resolved[proto_id]
        row = by_id.get(proto_id)
        if not row: return {}
        if proto_id in visiting or depth > 30: return row.get('components') or {}
        visiting.add(proto_id)
        effective = {}
        for parent_id in row.get('parent_ids') or []:
            parent = visit(parent_id, depth + 1)
            for component_type, component in parent.items():
                effective[component_type] = deep_merge(effective.get(component_type, {}), component)
        for component_type, component in (row.get('components') or {}).items():
            effective[component_type] = deep_merge(effective.get(component_type, {}), component)
        visiting.discard(proto_id)
        resolved[proto_id] = effective
        return effective
    for row in rows:
        row['components'] = visit(row['prototype_id'])
        # Inherited clothing slots and sprites are useful for the build UI.
        clothing = row['components'].get('Clothing', {})
        if not row.get('slots') and isinstance(clothing, dict):
            slots = clothing.get('slots', [])
            if isinstance(slots, str): slots = [slots]
            if isinstance(slots, list): row['slots'] = sorted(set(str(slot) for slot in slots))
        sprite = row['components'].get('Sprite', {})
        if not row.get('sprite_path') and isinstance(sprite, dict) and isinstance(sprite.get('sprite'), str):
            row['sprite_path'] = sprite['sprite']
    return rows

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--fork', default='all', help='fork id from config.py, or all')
    parser.add_argument('--output', default=str(OUT))
    parser.add_argument('--max-files-per-fork', type=int, default=0, help='debug/testing limit; 0 means all matching files')
    args = parser.parse_args()
    registry = fork_registry()
    selected = registry if args.fork == 'all' else {args.fork: registry[args.fork]} if args.fork in registry else None
    if not selected:
        print(f'Unknown fork {args.fork}. Known: {", ".join(registry)}', file=sys.stderr); return 2
    all_items, reports = [], []
    print(f'Equipment scan: {len(selected)} fork(s). Cache: {CACHE}')
    for fork_id, conf in selected.items():
        print(f'[{fork_id}] {conf.get("repo")} @ {conf.get("branch","master")}')
        items, report = fetch_fork(fork_id, conf, args.max_files_per_fork)
        all_items.extend(items); reports.append(report)
        print(f'  RESULT: {report.get("items",0)} prototypes')
    # A stable output makes diffs/cache debugging manageable.
    all_items.sort(key=lambda r: (r['source_fork'], r['category'], r['name'].casefold(), r['prototype_id']))
    payload = {'schema_version':1,'generated_at':time.strftime('%Y-%m-%dT%H:%M:%SZ', time.gmtime()),'items':all_items,'reports':reports}
    target = Path(args.output); target.parent.mkdir(parents=True, exist_ok=True)
    temp = target.with_suffix(target.suffix+'.tmp'); temp.write_text(json.dumps(payload, ensure_ascii=False, separators=(',',':')), encoding='utf-8'); temp.replace(target)
    print(f'Wrote {len(all_items)} item prototypes to {target}')
    return 0 if all_items else 1

if __name__ == '__main__':
    raise SystemExit(main())
