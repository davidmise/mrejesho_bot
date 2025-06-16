const axios = require("axios");
const fs = require("fs");
const feedbackMap = {};        // Tracks feedback state
const activatedUsers = {};     // Tracks activated users

async function getOrganizationByCode(secretCode) {
  try {
    const response = await axios.get("http://158.220.102.111:81/org_lookup.php", {
      params: { code: secretCode }
    });
    return response.data.organization; // { id, name, ... }
  } catch (err) {
    console.error("Organization lookup failed:", err.message);
    return null;
  }
}

async function handleFeedback(sock, sender, text) {
  if (!text || typeof text !== "string") {
    console.log(`⚠️ Ignored non-text message from ${sender}:`, text);
    return;
  }

  const normalizedText = text.trim().toLowerCase();

  // ✅ Activate bot if secret code is detected any time
  if (!activatedUsers[sender] && normalizedText.includes("hello")) {
    const org = await getOrganizationByCode(normalizedText);
    if (org) {
      activatedUsers[sender] = { orgId: org.id };
      feedbackMap[sender] = { step: "awaiting_message" };

      return await sock.sendMessage(sender, {
        text: `✅ Welcome to ${org.name}!\nPlease share your feedback message:`
      });
    } else {
      return await sock.sendMessage(sender, {
        text: "❌ Invalid code. Please check and try again."
      });
    }
  }

  // ❌ If not activated, just ignore message (let conversation continue normally)
  if (!activatedUsers[sender]) {
    console.log(`Ignoring message from ${sender} until secret code is received.`);
    return;
  }

  // ✅ Continue with feedback process
  if (!feedbackMap[sender]) {
    feedbackMap[sender] = { step: "awaiting_message" };
    await sock.sendMessage(sender, {
      text: "Hello! 👋 Please share your feedback message:"
    });
  } else if (feedbackMap[sender].step === "awaiting_message") {
    feedbackMap[sender].message = text;
    feedbackMap[sender].step = "awaiting_rating";

    await sock.sendMessage(sender, {
      text: "🙏 Thanks! Now please rate our service from 1 to 10:"
    });
  } else if (feedbackMap[sender].step === "awaiting_rating") {
    const rating = parseInt(text);

    if (isNaN(rating) || rating < 1 || rating > 10) {
      return await sock.sendMessage(sender, {
        text: "⚠️ Please enter a valid number between 1 and 10."
      });
    }

    const orgId = activatedUsers[sender].orgId;
    const feedbackData = {
      sender_number: sender,
      message: feedbackMap[sender].message,
      rating,
      organization_id: orgId
    };


    try {
      console.log("Sending feedback:", feedbackData);
      fs.appendFileSync("sent_feedback.log", JSON.stringify(feedbackData) + "\n");

      await axios.post("http://158.220.102.111:81/feedback_api.php", feedbackData, {
        headers: { "Content-Type": "application/json" }
      });

      await sock.sendMessage(sender, {
        text: "✅ Thank you for your feedback and rating! 🙏\n👋 Bot session is now closed. You can type the secret code again to start over."
      });
    } catch (error) {
      console.error("❌ Failed to submit feedback:", error.message);
      await sock.sendMessage(sender, {
        text: "🚫 Failed to submit feedback. Please try again later."
      });
    }

    // 🔒 Reset user session (deactivate bot for them)
    delete feedbackMap[sender];
    delete activatedUsers[sender];
  }
}

module.exports = { handleFeedback };
