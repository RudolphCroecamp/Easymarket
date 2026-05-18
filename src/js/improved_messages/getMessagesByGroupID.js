import {BACKEND_URL} from "../config.js"
import {my_message, their_message} from "./message_helpers.js"

export {getMessagesForChat, renderChatMessages}


async function getMessagesForChat(productID){
    //check if we have received a groupID
    if(!productID) {
        return Promise.reject("Could not get messages. No productID passed as parameter.")
    }

    try {
        return fetch(`${BACKEND_URL}/improved_messages/getAllMessagesByGroupID.php`,{
            method : "POST",
            credentials : "include",
            body : JSON.stringify({productID})
        })
        .then(res => res.json())
        .then(response =>{
            console.log(response);
            //invalid response
            if(response.success === false){
                throw response.error || "Could not load messages for chat"
            }

            return [response.messages, response.isOwner]

        })
        .catch(error => {
            throw error
        })

    } catch (error) {
        throw error
    }
}


async function renderChatMessages(productID){
    try {
        const [messages, isOwner] = await getMessagesForChat(productID)
        console.log(messages);

        //if no messages where sent yet
        if(!messages || messages.length <= 0){
            // const messageInput = document.getElementById("messageInput").value = "Hi, is this still available"
            return
        }
        
        //append messages
        const container = document.getElementById("message_container");
        messages.forEach(message => {
            if(message.sentByCurrentUser){
                container.innerHTML+= my_message(message.message)
            }else{
                container.innerHTML+= their_message(message.message, isOwner)
            }
        });

    } catch (error) {
        throw error
    }

}










