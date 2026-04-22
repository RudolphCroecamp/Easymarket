import {BACKEND_URL} from "../config.js"

export default function getComments(productID, page=1){

    console.log("getting comments");

    if(!productID){
        return Promise.reject("No product ID passed");
    }


    return fetch(`${BACKEND_URL}/comments/getComments.php?page=${page}&productID=${productID}`,{
        method : "POST",
        credentials : "include"
    })
    .then(res => res.json())
    .then(data =>{
        
        console.log(data);

        if(data.success == false){
            throw new Error(data.error);
        }

        return data

    }).catch(e =>{
        console.log(e);
        throw e
    })
}