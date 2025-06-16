const { handleFeedback } = require('./feedback');

// Fake socket with mock sendMessage
const fakeSock = {
  sendMessage: async (sender, message) => {
    console.log(`📤 Reply to ${sender}:`, message.text);
  }
};

// Simulate messages
(async () => {
  const sender = "user1@s.whatsapp.net";
  console.log("➡️ First message (should ask for feedback)");
  await handleFeedback(fakeSock, sender, "Hi!");

  console.log("\n➡️ Second message (should store feedback)");
  await handleFeedback(fakeSock, sender, "This service is great!");
})();
