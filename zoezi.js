    // iphoneScanner.js
    const xlsx = require('./sheetjs/xlsx.js');

    const iphoneEntries = [];

    const pattern = /^(\d{1,2})\s+(plain|plus|mini|pro max|pro)?\s+(\d+)\s+(\d+)\s+(\d+)\s+(.+?)\s+(used|clean|new|brand new)$/i;

    function processIphoneMessage(text) {
    const match = text.match(pattern);
    if (!match) return null;

    const [, model, variant, storage, buying, selling, supplier, condition] = match;
    return {
        Model: `iPhone ${model}`,
        Variant: (variant || "plain").trim(),
        Storage: `${storage}GB`,
        BuyingPrice: parseInt(buying),
        SellingPrice: parseInt(selling),
        Supplier: supplier.trim(),
        Condition: condition.trim(),
    };
    }

    function handleIphoneText(text) {
    const data = processIphoneMessage(text);
    if (data) iphoneEntries.push(data);
    }

    function saveIphoneData(file = "iphone_data.xlsx") {
    const ws = xlsx.utils.json_to_sheet(iphoneEntries);
    const wb = xlsx.utils.book_new();
    xlsx.utils.book_append_sheet(wb, ws, "Phones");
    xlsx.writeFile(wb, file);
    }

    module.exports = { handleIphoneText, saveIphoneData };
