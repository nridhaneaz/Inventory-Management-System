const UNIT_ALIASES = {
    kg: 'kg',
    kilogram: 'kg',
    kilograms: 'kg',
    kilo: 'kg',
    gm: 'gm',
    g: 'gm',
    gram: 'gm',
    grams: 'gm',
    pcs: 'pcs',
    pc: 'pcs',
    piece: 'pcs',
    pieces: 'pcs',
    l: 'l',
    liter: 'l',
    litre: 'l',
    liters: 'l',
    litres: 'l',
    ml: 'ml',
    milliliter: 'ml',
    millilitre: 'ml',
    milliliters: 'ml',
    millilitres: 'ml',
};

const numberPattern = '(\\d+(?:,\\d{3})*(?:\\.\\d+)?)';
const messagePricePattern = new RegExp(`(?:^|[\\s|:-])(${numberPattern})(?=\\s*(?:\\u09f3|tk|taka|bdt)\\b|\\s*$)`, 'i');
const quantityUnitPattern = new RegExp(`\\b${numberPattern}\\s*(kg|kilograms?|kilo|gm|g|grams?|pcs|pc|pieces?|ml|millilit(?:er|re)s?|l|lit(?:er|re)s?)\\b`, 'i');
// A common Messenger shorthand is "Item name - 130 Tk". It has no quantity
// or unit, so it represents one piece of the manual item.
const priceOnlyPattern = new RegExp(`(?:^|[\\s|:–—-])(${numberPattern})(?=\\s*(?:৳|tk|taka|bdt)\\b|\\s*$)`, 'i');

const toNumber = (value) => Number(String(value).replace(/,/g, ''));

const cleanName = (value) => value
    .replace(/[|:–—-]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();

const parseLine = (rawLine) => {
    const line = String(rawLine || '').replace(/\s+/g, ' ').trim();
    if (!line) return { item: null, error: null };

    // A Messenger price is the total for the pasted line. Pack sizes such as
    // "500 gm" and "15 pcs" stay in the description instead of multiplying it.
    const finalPriceMatch = line.match(messagePricePattern);
    if (!finalPriceMatch) {
        return { item: null, error: 'Could not detect a final Tk price for one or more lines.' };
    }

    const priceStart = line.indexOf(finalPriceMatch[1], finalPriceMatch.index || 0);
    const name = line
        .slice(0, priceStart)
        .replace(/^[০-৯0-9]+\s*[).,]+\s*/, '')
        .replace(/\s*-\s*$/, '')
        .replace(/[|:–—-]+$/g, '')
        .replace(/\s+/g, ' ')
        .trim();
    const rate = toNumber(finalPriceMatch[1]);

    if (!name) return { item: null, error: 'Could not detect item name.' };
    if (!Number.isFinite(rate) || rate < 0) return { item: null, error: `Could not detect price for ${name}.` };

    return { item: { name, quantity: 1, unit: 'pcs', rate }, error: null };
};

/**
 * Parses one Messenger order per line. The numeric price is treated as the
 * existing manual-row rate, so amount remains quantity × rate.
 */
export const parseMessengerOrder = (text) => {
    const items = [];
    const errors = [];

    String(text || '').replace(/\r\n?/g, '\n').split('\n').forEach((line) => {
        const { item, error } = parseLine(line);
        if (item) items.push(item);
        if (error) errors.push(error);
    });

    return { items, errors: [...new Set(errors)] };
};

export const formatMessengerOrder = (items) => items
    .filter((item) => item?.is_custom_item && String(item.name || '').trim())
    .map((item) => {
        const quantity = Number(item.quantity || 0);
        const rate = Number(item.unit_price || 0);
        const unit = { pcs: 'PCS', kg: 'KG', gm: 'GM', l: 'L', ml: 'ML' }[item.unit_type] || 'PCS';
        return `${item.name.trim()} - ${quantity} ${unit} - ${rate} Tk`;
    })
    .join('\n');
