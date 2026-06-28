import {BACKEND_URL} from "../config.js"
import {my_message, their_message} from "./message_helpers.js"

export {getMessagesForChat, renderChatMessages}


async function getMessagesForChat(productID) {
    console.log(productID);
    if (!productID) {
        throw new Error("Could not get messages. No productID passed as parameter.");
    }

    const res = await fetch(`${BACKEND_URL}/improved_messages/getAllMessagesByGroupID.php`, {
        method: "POST",
        credentials: "include",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ productID })
    });

    const response = await res.json();

    console.log(response);

    if (!res.ok || response.success === false) {
        throw new Error(response.error || "Could not load messages for chat");
    }

    return [response.messages, response.isOwner];
}


async function renderChatMessages(productID) {
    const container = document.getElementById("message_container");

    try {
        const [messages, isOwner] = await getMessagesForChat(productID);

        if (!messages || messages.length === 0) {
            throw new Error("No messages");
        }

        let html = "";

        messages.forEach(message => {
            if (message.sentByCurrentUser) {
                html += my_message(message.message);
            } else {
                html += their_message(message.message, isOwner);
            }
        });

        container.innerHTML = html;

    } catch (error) {
        console.log(error.message || error);
        container.innerHTML = `<p class="text-muted">No messages yet</p>`;
    }
}










