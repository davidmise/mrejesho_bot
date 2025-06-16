const { handleIphoneText, saveIphoneData } = require('./zoezi');

// Simulate input
handleIphoneText("15 plain 128 1900000 2100000 Adon traders used");
handleIphoneText("14 pro max 256 2500000 2700000 iWorld clean");

// Save to Excel
saveIphoneData("test_iphones.xlsx");

console.log("✅ iPhone data saved to test_iphones.xlsx");
