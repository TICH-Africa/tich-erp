import re
from pathlib import Path

for path in Path("lib").rglob("*.dart"):
    text = path.read_text(encoding="utf-8")
    keys = sorted(set(re.findall(r"persistKey: '([^']+)'", text)))
    if not keys:
        continue
    orig = text
    key = keys[0]  # primary screen key per file
    if len(keys) == 1:
        k = key
    else:
        # prefer portal./student./staff./procurement. prefix keys
        k = next((x for x in keys if x.startswith(("portal.", "student.", "staff.", "procurement."))), key)

    def inject(block: str) -> str:
        if "persistKey:" in block:
            return block
        if "onData: (data) => _dataCache = data," in block:
            return block.replace(
                "onData: (data) => _dataCache = data,",
                f"onData: (data) => _dataCache = data,\n      persistKey: '{k}',",
            )
        if "onData: (d) => _cache = d," in block:
            return block.replace(
                "onData: (d) => _cache = d,",
                f"onData: (d) => _cache = d,\n      persistKey: '{k}',",
            )
        return block

    text = re.sub(
        r"tichSilentReload\(\s*\n(?:.*?\n)*?\n\s*\);",
        lambda m: inject(m.group(0)),
        text,
        flags=re.DOTALL,
    )
    if text != orig:
        path.write_text(text, encoding="utf-8")
        print(path)
