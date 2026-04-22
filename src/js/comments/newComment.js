import {BACKEND_URL} from "../config.js"

export default function newComment(reviewData){
    if(!reviewData){
        return Promise.reject("No review data");
    }
    

    return fetch(`${BACKEND_URL}/comments/newComment.php`,{
        method : "POST",
        credentials : "include",
        headers: {
            "Content-Type": "application/json"
        },
        body : JSON.stringify(reviewData)
    })
    .then(res => res.json())
    .then(data => {
        console.log(data);

        if(data.success == false){
            throw new Error(data.error);
        }
    })
    .catch(error =>{
        console.log(error)
        //show error message to client
        document.getElementById("error-box").classList.remove("visually-hidden");
        document.getElementById("error-message").innerText = error;
    })

}