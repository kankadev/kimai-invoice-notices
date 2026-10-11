"""Create a deterministic Kimai plugin archive from explicitly approved public paths."""
from pathlib import Path
import argparse
import hashlib
import json
import re
import zipfile

ROOT = Path(__file__).resolve().parents[1]
FILES = ["composer.json", "KankaInvoiceNoticesBundle.php", "README.md", "LICENSE", "SECURITY.md", "CHANGELOG.md", "TODO.md"]
DIRECTORIES = ["Controller", "DependencyInjection", "EventSubscriber", "Exception", "Form", "Resources", "Service", "Tests", "docs"]


def package(output: Path) -> Path:
    version = json.loads((ROOT / "composer.json").read_text(encoding="utf-8"))["version"]
    if not re.fullmatch(r"[0-9]+\.[0-9]+\.[0-9]+(?:-[A-Za-z0-9.-]+)?", version):
        raise ValueError("A valid release version is required")
    paths = [ROOT / name for name in FILES]
    for directory in DIRECTORIES:
        paths.extend(path for path in (ROOT / directory).rglob("*") if path.is_file())
    output.mkdir(parents=True, exist_ok=True)
    target = output / f"KankaInvoiceNoticesBundle-{version}.zip"
    with zipfile.ZipFile(target, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for path in sorted(paths):
            if path.is_symlink() or not path.resolve().is_relative_to(ROOT):
                raise ValueError("Release files must remain inside the public source tree")
            info = zipfile.ZipInfo("KankaInvoiceNoticesBundle/" + path.relative_to(ROOT).as_posix(), date_time=(2026, 1, 1, 0, 0, 0))
            info.compress_type = zipfile.ZIP_DEFLATED
            info.create_system = 3
            info.external_attr = 0o100644 << 16
            archive.writestr(info, path.read_bytes(), compress_type=zipfile.ZIP_DEFLATED, compresslevel=9)
    checksum = hashlib.sha256(target.read_bytes()).hexdigest()
    target.with_suffix(".zip.sha256").write_text(f"{checksum}  {target.name}\n", encoding="ascii")
    return target


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--output", type=Path, default=ROOT / "dist")
    print(package(parser.parse_args().output))
