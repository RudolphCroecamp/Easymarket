import {BACKEND_URL} from "../config.js"

import {my_message, their_message} from "./message_helpers.js"

export {sendMessage}





function sendMessage(productID, groupID, message, message_container){
    try {
        //check if we have received a productID
        if(!productID || productID.trim() == "") {
            return Promise.reject("Could not get messages. No productID passed as parameter.")
        }

        //check if we have received a message
        if(!message || message.trim() == "") {
            return Promise.reject("Could not get messages. No message passed as parameter.")
        }


        //send message to server

        return fetch(`${BACKEND_URL}/improved_messages/newMessage.php`, {
            method : "POST",
            credentials : "include",
            body : JSON.stringify({productID, groupID, message})
        })
        .then(res => res.json())//convert result to json format
        .then(response =>{
            console.log(response);

            //request failed
            if(response.success === false){
                throw new Error(response.error || "Could not send message. Please try again later");
            }
            
            //request success
             console.log( message_container);
            //append message to container
            message_container.innerHTML += my_message(message)

            console.log(message);

            //go to last message
            message_container.scrollTop = message_container.scrollHeight;

            return response.message

        })
        .catch(error => {throw error})


    } catch (error) {
        console.log(error);
        throw error
    }

}