#!/usr/bin/env python3
"""Build the external-brand SureCart import snapshot."""

from __future__ import annotations

import csv
import html
import json
import re
import urllib.request
from datetime import date
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
SOURCES = (
    ("CaviarEat", "caviareat", "https://caviareat.it"),
    ("Truffleat", "truffleat", "https://truffleat.it"),
    ("Tin Caviar", "tin-caviar", "https://tincaviar.com"),
)
LUXUREAT_HANDLES = {
    "caviale-beluga-100-huso-huso", "caviale-amur", "caviale-royal-baerii-30-gr",
    "caviale-russian-oscietra", "caviale-royal-kaluga-30-gr", "caviale-russian-oscietra-30-gr",
    "tartare-di-gambero-rosa-di-mazara-del-vallo", "grani-di-gambero-rosso-di-mazara-del-vallo-50-gr",
    "tartare-di-gambero-rosso-di-mazara-del-vallo", "tartare-di-mazzancolla", "tartare-di-pesce-spada",
    "tartare-di-tonno", "tartare-di-scampi", "tartare-di-ricciola",
    "burger-di-fassona-al-tartufo-bianco-200-gr", "burger-di-fassona-al-tartufo-nero-200-gr",
    "olio-di-oliva-al-tartufo-nero-250-ml", "olio-di-oliva-al-tartufo-bianco-250-ml",
    "olio-extra-vergine-di-oliva-al-peperoncino-di-calabria-250-ml",
    "olio-di-vinacciolo-al-tartufo-bianco-naturale-250-ml", "salsa-di-soia-al-wasabi-e-tartufo-100-ml",
    "trufflemist-spray-al-tartufo-100-ml", "salsa-detox-al-tartufo-bianco", "salsa-detox-al-tartufo-nero",
    "caviale-vegano", "maionese-vegana-al-tartufo", "cioccolatini-al-tartufo-nero-e-bianco-50-gr",
    "cioccolatini-desire-al-tartufo-e-peperoncino-50-gr",
}
LUXUREAT_UNPRICED = (
    ("Salmon Tartare", "salmon-tartare", "LuxurEat salmon tartare, prepared with selected salmon for refined appetizers and seafood dishes.", "https://luxureat.com/fish-tartare-category/", "https://luxureat.com/wp-content/uploads/elementor/thumbs/tartare-di-salmone-rhlmicibzklkz588hffr64rdtjgwp91xomf961nfrc.png"),
    ("Black Winter Truffle", "precious-black-truffle", "Black Winter Truffle (Tuber melanosporum), prized for its intense and refined aroma.", "https://luxureat.com/precious-black-truffle/", "https://luxureat.com/wp-content/uploads/2025/07/tartufo-nero-pregiato.jpg"),
    ("White Truffle", "white-truffle-tuber-magnatum", "White Truffle (Tuber magnatum Pico), appreciated for its unique and unmistakable fragrance.", "https://luxureat.com/white-truffle-tuber-magnatum/", "https://luxureat.com/wp-content/uploads/2025/07/tartufo-bianco.jpg"),
    ("Bianchetto Truffle", "bianchetto-truffle-tuber-albidum-pico", "Bianchetto Truffle (Tuber borchii Vitt.), known for its delicate and pleasant aroma.", "https://luxureat.com/bianchetto-truffle-tuber-albidum-pico/", "https://luxureat.com/wp-content/uploads/2025/07/tartufo-bianchetto.jpg"),
    ("Summer Truffle", "summer-black-truffle-tuber-aestivum", "Summer Truffle (Tuber aestivum Vitt.), a versatile truffle with a delicate flavor.", "https://luxureat.com/summer-black-truffle-tuber-aestivum/", "https://luxureat.com/wp-content/uploads/2025/07/tartufo-estivo.jpg"),
    ("Whole Summer Truffle", "whole-summer-truffle", "Whole Summer Truffle, carefully preserved to maintain its natural aroma.", "https://luxureat.com/whole-summer-truffle/", "https://luxureat.com/wp-content/uploads/2025/07/tartufo-intero-estivo.jpg"),
    ("Summer Truffle Slices", "summer-truffle-slices", "Summer truffle slices ready for gourmet preparations and finishing dishes.", "https://luxureat.com/summer-truffle-slices/", "https://luxureat.com/wp-content/uploads/2025/07/lamelle-di-tartufo-estivo.jpg"),
)


def fetch(url: str) -> dict:
    request = urllib.request.Request(url, headers={"User-Agent": "UgoliniGroupCatalog/1.0"})
    with urllib.request.urlopen(request, timeout=30) as response:
        return json.load(response)


def text_from_html(value: str) -> str:
    value = re.sub(r"<(script|style)[^>]*>.*?</\1>", "", value or "", flags=re.I | re.S)
    value = re.sub(r"<h2[^>]*>", "\n## ", value, flags=re.I)
    value = re.sub(r"<h3[^>]*>", "\n### ", value, flags=re.I)
    value = re.sub(r"<br\s*/?>|</(?:p|li|h[1-6]|div)>", "\n", value, flags=re.I)
    value = re.sub(r"<[^>]+>", "", value)
    value = html.unescape(value).replace("\xa0", " ")
    value = re.sub(r"[ \t]+\n", "\n", value)
    return re.sub(r"\n\s*\n+", "\n", value).strip()


def short_description(body_html: str) -> str:
    text = text_from_html(body_html)
    paragraph = next((part.strip() for part in text.splitlines() if len(part.strip()) > 80), text)
    return paragraph[:500].rstrip()


SEMANTIC_SECTIONS = (
    ("ingredients", re.compile(r"\bingredient", re.I)),
    ("allergens", re.compile(r"\ballergen", re.I)),
    ("nutritional_information", re.compile(r"nutriz|energia|grassi|carboidrati|proteine|zuccheri|\bsale\b|sodio", re.I)),
    ("storage_instructions", re.compile(r"conserv|scongel|ricongel|temperatura|catena del freddo|scadenza", re.I)),
    ("shelf_life", re.compile(r"shelf[ -]?life|durata", re.I)),
    ("usage_instructions", re.compile(r"modalit[aà].*(?:uso|consumo)|come (?:us|utilizz|serv)|consigli? d.?uso|preparazione|abbinament|ideale per|perfett[oa] per|consiglio da chef", re.I)),
    ("weight_format", re.compile(r"\bformati?\b|peso netto|contenuto netto", re.I)),
    ("organoleptic_characteristics", re.compile(r"organolett|profilo sensoriale|profilo aromatico|aroma e gusto|\btexture\b", re.I)),
    ("certifications_dietary_claims", re.compile(r"certific|sicurezza|origine|tracciabil|perch[eé] scegliere|domande frequenti|\bfaq\b|senza glutine", re.I)),
)


def semantic_product_fields(description: str) -> dict[str, str]:
    """Group copy by meaning into a stable product-information hierarchy."""
    buckets: dict[str, list[str]] = {"full_description": []}
    current = "full_description"

    for raw_line in (description or "").splitlines():
        line = re.sub(r"^#{2,3}\s*", "", raw_line).strip(" \t:-")
        if not line or line.lower().startswith("scheda originale"):
            continue

        heading = raw_line.lstrip().startswith("##")
        matched = next((key for key, pattern in SEMANTIC_SECTIONS if pattern.search(line)), None)
        if matched and (heading or len(line) < 90 or matched in {"nutritional_information", "storage_instructions"}):
            current = matched
            buckets.setdefault(current, [])
            # Keep inline facts such as "Formato: 250 ml", but discard navigation-like labels.
            if ":" in line and len(line.split(":", 1)[1].strip()) > 1:
                buckets[current].append(line)
            continue

        buckets.setdefault(current, []).append(line)

    return {key: "\n".join(lines).strip() for key, lines in buckets.items() if lines}


def cents(value: str) -> int:
    return round(float(value) * 100)


def product_row(product: dict, brand: str, site: str, source_url: str, include_unavailable: bool = False) -> dict:
    variants = product["variants"] if include_unavailable else [variant for variant in product["variants"] if variant.get("available")]
    if not variants:
        raise ValueError(f"{brand}/{product['handle']} has no importable variants")

    meaningful_options = [option for option in product["options"] if option["name"] != "Title"]
    prices = [cents(variant["price"]) for variant in variants]
    available = any(variant.get("available") for variant in variants)
    import_product = {
        "name": product["title"],
        "slug": product["handle"],
        "description": short_description(product.get("body_html", "")),
        "status": "published",
        "shipping_enabled": True,
        "stock_enabled": not available,
        "allow_out_of_stock_purchases": available,
        "tax_enabled": True,
        "tax_category": "tangible",
        "product_collection_slugs": [site],
        "prices": [{
            "name": variants[0]["title"] if len(variants) == 1 and variants[0]["title"] != "Default Title" else None,
            "amount": min(prices),
            "currency": "eur",
        }],
        "product_medias": [{"url": image["src"]} for image in product.get("images", [])],
        "metadata": {
            "source_store": brand,
            "source_product_id": str(product["id"]),
            "source_url": f"{source_url}/products/{product['handle']}",
        },
        "content": (
            '<!-- wp:html --><div class="ugolini-imported-product-content">'
            + (product.get("body_html") or "")
            + f'<p><a href="{source_url}/products/{product["handle"]}" rel="noopener">Scheda originale {html.escape(brand)}</a></p>'
            + "</div><!-- /wp:html -->"
        ),
    }
    if not available:
        import_product["available_stock"] = 0

    if len(variants) > 1 and meaningful_options:
        import_product["variant_options"] = [
            {
                "name": option["name"],
                "position": index,
                "display_type": "dropdown" if len(option["values"]) > 6 else "radio",
            }
            for index, option in enumerate(meaningful_options)
        ]
        import_product["variants"] = []
        for position, variant in enumerate(variants):
            is_available = bool(variant.get("available"))
            item = {
                "amount": cents(variant["price"]),
                "position": position,
                "stock_enabled": not is_available,
                "allow_out_of_stock_purchases": is_available,
                "shipping_enabled": True,
                "metadata": {"source_variant_id": str(variant["id"])},
            }
            if not is_available:
                item["available_stock"] = 0
            for option_number in range(1, len(meaningful_options) + 1):
                item[f"option_{option_number}"] = variant.get(f"option{option_number}")
            if variant.get("sku"):
                item["sku"] = variant["sku"]
            import_product["variants"].append(item)

    source_product_url = f"{source_url}/products/{product['handle']}"
    semantic = semantic_product_fields(text_from_html(product.get("body_html", "")))
    csv_row = {
        "source_wordpress_id": "",
        "source_surecart_product_id": f"{site}:{product['id']}",
        "slug": product["handle"],
        "official_product_name": product["title"],
        "currency": "EUR",
        "current_price": f"{min(prices) / 100:.2f}",
        "sale_price": "",
        "is_on_sale": "false",
        "collections": brand,
        "source_url": source_product_url,
        "image_urls": " | ".join(image["src"] for image in product.get("images", [])),
        "short_description": import_product["description"],
        "full_description": semantic.get("full_description", import_product["description"]),
        "ingredients": semantic.get("ingredients", ""),
        "allergens": semantic.get("allergens", ""),
        "nutritional_information": semantic.get("nutritional_information", ""),
        "shelf_life": semantic.get("shelf_life", ""),
        "storage_instructions": semantic.get("storage_instructions", ""),
        "usage_instructions": semantic.get("usage_instructions", ""),
        "primary_packaging": "",
        "weight_format": semantic.get("weight_format", "") or " | ".join(variant["title"] for variant in variants if variant["title"] != "Default Title"),
        "certifications_dietary_claims": semantic.get("certifications_dietary_claims", ""),
        "organoleptic_characteristics": semantic.get("organoleptic_characteristics", ""),
        "source_stock_enabled": str(not available).lower(),
        "source_available_stock": "" if available else "0",
        "source_allow_out_of_stock_purchases": str(available).lower(),
        "source_shipping_enabled": "true",
        "source_last_modified": product.get("updated_at", ""),
        "audit_date": date.today().isoformat(),
    }
    return {"import": import_product, "csv": csv_row}


def unpriced_product_row(name: str, slug: str, description: str, source_url: str, image_url: str) -> dict:
    product = {
        "name": name, "slug": slug, "description": description, "status": "published",
        "shipping_enabled": True, "stock_enabled": True, "available_stock": 0,
        "allow_out_of_stock_purchases": False, "tax_enabled": True, "tax_category": "tangible",
        "product_collection_slugs": ["luxureat"], "product_medias": [{"url": image_url}],
        "metadata": {"source_store": "LuxurEat", "source_url": source_url, "price_status": "not_published"},
        "content": '<!-- wp:paragraph --><p>' + html.escape(description) + '</p><!-- /wp:paragraph -->'
            + '<!-- wp:paragraph --><p><a href="' + source_url + '" rel="noopener">Scheda originale LuxurEat</a></p><!-- /wp:paragraph -->',
    }
    csv_row = {
        "source_wordpress_id": "", "source_surecart_product_id": f"luxureat:{slug}", "slug": slug,
        "official_product_name": name, "currency": "EUR", "current_price": "", "sale_price": "",
        "is_on_sale": "false", "collections": "LuxurEat", "source_url": source_url, "image_urls": image_url,
        "short_description": description, "full_description": description, "ingredients": "", "allergens": "",
        "nutritional_information": "", "shelf_life": "", "storage_instructions": "", "usage_instructions": "",
        "primary_packaging": "", "weight_format": "", "certifications_dietary_claims": "",
        "organoleptic_characteristics": "", "source_stock_enabled": "true", "source_available_stock": "0",
        "source_allow_out_of_stock_purchases": "false", "source_shipping_enabled": "true",
        "source_last_modified": "", "audit_date": date.today().isoformat(),
    }
    return {"import": product, "csv": csv_row}


def main() -> None:
    output = {"generated_at": date.today().isoformat(), "collections": [], "products": []}
    csv_rows = []
    seen_slugs = set()
    for brand, slug, source_url in SOURCES:
        output["collections"].append({"name": brand, "slug": slug, "description": f"Prodotti {brand}."})
        payload = fetch(f"{source_url}/products.json?limit=250")
        for product in payload["products"]:
            built = product_row(product, brand, slug, source_url)
            output["products"].append(built["import"])
            csv_rows.append(built["csv"])
            seen_slugs.add(product["handle"])

    brand, slug, source_url = "LuxurEat", "luxureat", "https://luxureat.it"
    output["collections"].append({"name": brand, "slug": slug, "description": "Prodotti LuxurEat."})
    payload = fetch(f"{source_url}/products.json?limit=250")
    missing_handles = LUXUREAT_HANDLES - {product["handle"] for product in payload["products"]}
    if missing_handles:
        raise ValueError("Missing LuxurEat products: " + ", ".join(sorted(missing_handles)))
    for product in payload["products"]:
        if product["handle"] not in LUXUREAT_HANDLES:
            continue
        if product["handle"] in seen_slugs:
            existing_product = next(item for item in output["products"] if item["slug"] == product["handle"])
            existing_product["product_collection_slugs"].append(slug)
            existing_row = next(item for item in csv_rows if item["slug"] == product["handle"])
            existing_row["collections"] += " | " + brand
            continue
        built = product_row(product, brand, slug, source_url, include_unavailable=True)
        output["products"].append(built["import"])
        csv_rows.append(built["csv"])
        seen_slugs.add(product["handle"])
    for product in LUXUREAT_UNPRICED:
        built = unpriced_product_row(*product)
        output["products"].append(built["import"])
        csv_rows.append(built["csv"])
        seen_slugs.add(product[1])

    destination = ROOT / "migration" / "ugolini-external-products"
    destination.mkdir(parents=True, exist_ok=True)
    (destination / "products.json").write_text(json.dumps(output, ensure_ascii=False, indent=2) + "\n")

    products_csv = ROOT / "data" / "products.csv"
    with products_csv.open(newline="") as handle:
        existing = list(csv.DictReader(handle))
        fields = list(existing[0])
    existing = [row for row in existing if ":" not in row["source_surecart_product_id"]]
    with products_csv.open("w", newline="") as handle:
        writer = csv.DictWriter(handle, fieldnames=fields, lineterminator="\n")
        writer.writeheader()
        writer.writerows(existing + csv_rows)

    collections_csv = ROOT / "data" / "collections.csv"
    with collections_csv.open(newline="") as handle:
        collections = list(csv.DictReader(handle))
        collection_fields = list(collections[0])
    source_collections = SOURCES + (("LuxurEat", "luxureat", "https://luxureat.com"),)
    collections = [row for row in collections if row["slug"] not in {slug for _, slug, _ in source_collections}]
    for brand, slug, source_url in source_collections:
        products = [row["official_product_name"] for row in csv_rows if brand in row["collections"].split(" | ")]
        collections.append({
            "collection_name": brand,
            "slug": slug,
            "source_url": source_url,
            "products": " | ".join(products),
            "product_count": str(len(products)),
            "source_description": f"Prodotti {brand}.",
        })
    with collections_csv.open("w", newline="") as handle:
        writer = csv.DictWriter(handle, fieldnames=collection_fields, lineterminator="\n")
        writer.writeheader()
        writer.writerows(collections)

    print(f"Built {len(output['products'])} products in {len(output['collections'])} collections")


if __name__ == "__main__":
    main()
