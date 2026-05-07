import {BACKEND_URL} from "../config.js"
import { getMessagesForChat } from "./getMessagesByGroupID.js";
import { sendMessage } from "./newMessage.js";
import {my_message, their_message} from "./message_helpers.js"


getLatestMessages()

function getLatestMessages() {
    
    try {
        fetch(`${BACKEND_URL}/improved_messages/getLatestMessages.php`,{
            method : "POST",
            credentials : "include"
        })
        .then(res => res.json())
        .then(response =>{
            console.log(response);

            if(response.success === false){
                throw response.error || "Could not load chat list"
            }
            
            //load chats to chat canvas
            loadChatList(response.messages)

        })
        .catch(error => {throw error})

    } catch (error) {
        throw error
    }

    
}

function loadChatList(messages){
    const chat_canvas = document.getElementById("chat_canvas");
    console.log(messages);

    if(!messages && messages.length <= 0){
        console.log("No messages to load");
        return
    }



    messages.forEach(chat => {
        const div = document.createElement("div");

        //set data to request all messages later
        div.dataset.groupid = chat.groupID;
        div.dataset.name = `${chat.fName} ${chat.lName}`;

        div.className = "chat-item d-flex align-items-center p-3";

        div.innerHTML = `
            <img src="/src/images/downvotes.png" class="chat-avatar">

            <div class="ms-3 flex-grow-1">
                <div class="d-flex justify-content-between">
                    <h6 class="mb-0 chat-title">
                        ${chat.fName} • ${chat.lName}
                    </h6>
                    <small class="text-muted chat-date">
                        ${formatTime(chat.created_at)}
                    </small>
                </div>

                <p class="mb-0 text-muted chat-preview">
                    ${chat.latest_message}
                </p>
            </div>
        `;

        // click opens chat
        div.style.cursor = "pointer";

        div.addEventListener("click", () => {
            openChatModal(chat.productID, chat.groupID, `${chat.fName} ${chat.lName}`);
        });

        chat_canvas.appendChild(div);
    });

        

}


function formatTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

async function openChatModal(productID, groupID, name) {
    document.getElementById("chatUserName").innerText = name;

    //add group chat info to load chats later
    const modalElement = document.getElementById("chatModal")
    modalElement.dataset.productID = productID
    modalElement.dataset.groupID = groupID
    modalElement.dataset.name = name

    const modal = new bootstrap.Modal(modalElement);

    await loadModalMessages(productID)

    modal.show();
    
    //scroll to latest message on open
    modalElement.addEventListener('shown.bs.modal', () => {
        const chatMessagesContainer = document.getElementById("chatMessagesContainer");
        chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
    }, { once: true });
 
}


//add event listener for btnSendMessageModal
document.getElementById("btnSendMessageModal").addEventListener("click", ()=>{

    const modalElement = document.getElementById("chatModal")
    const groupID = modalElement.dataset.groupID
    const productID = modalElement.dataset.productID

    const chatMessagesContainer = document.getElementById("chatMessagesContainer")
    const messageInput = document.getElementById("messageInput")

    const message = messageInput.value


    try {
        sendMessage(productID, groupID, message, chatMessagesContainer)
        messageInput.value = ""
    } catch (error) {
        console.log(error);
    }
    
})



async function loadModalMessages(productID){
    try {
        const messages = await getMessagesForChat(productID);
        const chatMessagesContainer = document.getElementById("chatMessagesContainer")

        console.log(messages);

        chatMessagesContainer.innerHTML = "";

        messages.forEach(message=>{

            if(message.sentByCurrentUser){
                chatMessagesContainer.innerHTML+= my_message(message.message)
            }else{
                chatMessagesContainer.innerHTML+= their_message(message.message)
            }
        })

    } catch (error) {
        console.log(error);
    }
}


