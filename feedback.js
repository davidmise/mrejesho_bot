// save to text file 

// const fs = require("fs");

// const feedbackMap = {}; // Tracks feedback state per sender

// async function handleFeedback(sock, sender, text) {
//   if (!feedbackMap[sender]) {
//     // Step 1: Ask for feedback
//     feedbackMap[sender] = { step: "awaiting_message" };
//     await sock.sendMessage(sender, {
//       text: "Hello! 👋 I am Mrejesho Bot 🤖\nPlease share your feedback message:"
//     });
//   } else if (feedbackMap[sender].step === "awaiting_message") {
//     // Step 2: Save message and ask for rating
//     feedbackMap[sender].message = text;
//     feedbackMap[sender].step = "awaiting_rating";

//     await sock.sendMessage(sender, {
//       text: "🙏 Thanks! Now please rate our service from 1 to 5:"
//     });
//   } else if (feedbackMap[sender].step === "awaiting_rating") {
//     // Step 3: Save rating and write to file
//     const rating = parseInt(text);
//     if (isNaN(rating) || rating < 1 || rating > 5) {
//       return await sock.sendMessage(sender, {
//         text: "⚠️ Please enter a valid number between 1 and 5 for the rating."
//       });
//     }

//     const feedback = {
//       sender_number: sender,
//       message: feedbackMap[sender].message,
//       rating: rating
//     };

//     const line = JSON.stringify(feedback) + "\n";
//     fs.appendFileSync("feedback.txt", line, "utf8");

//     await sock.sendMessage(sender, {
//       text: "✅ Thank you for your feedback and rating! 🙏"
//     });

//     // Clear the sender's state
//     delete feedbackMap[sender];
//   }
// }

// module.exports = { handleFeedback };


// axios request.

const axios = require("axios");

const feedbackMap = {};

async function handleFeedback(sock, sender, text) {
  if (!feedbackMap[sender]) {
    feedbackMap[sender] = { step: "awaiting_message" };
    await sock.sendMessage(sender, {
      text: "Hello! 👋 I am Mrejesho Bot 🤖\nPlease share your feedback message:"
    });
  } else if (feedbackMap[sender].step === "awaiting_message") {
    feedbackMap[sender].message = text;
    feedbackMap[sender].step = "awaiting_rating";

    await sock.sendMessage(sender, {
      text: "🙏 Thanks! Now please rate our service from 1 to 5:"
    });
  } else if (feedbackMap[sender].step === "awaiting_rating") {
    const rating = parseInt(text);
    if (isNaN(rating) || rating < 1 || rating > 5) {
      return await sock.sendMessage(sender, {
        text: "⚠️ Please enter a valid number between 1 and 5."
      });
    }

    const feedbackData = {
      sender_number: sender,
      message: feedbackMap[sender].message,
      rating
    };

    try {
      await axios.post("http://localhost:8000/api/feedback", feedbackData); // adjust if needed

      await sock.sendMessage(sender, {
        text: "✅ Thank you for your feedback and rating! 🙏"
      });
    } catch (error) {
      console.error("❌ Failed to submit feedback:", error.message);
      await sock.sendMessage(sender, {
        text: "🚫 Failed to submit feedback. Please try again later."
      });
    }

    delete feedbackMap[sender];
  }
}

module.exports = { handleFeedback };
