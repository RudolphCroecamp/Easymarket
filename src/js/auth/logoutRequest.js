import {BACKEND_URL} from "../config.js"

logoutRequest();

//clear session on sever 
//redirect back to login page
function logoutRequest(){
    fetch(`${BACKEND_URL}/Auth/logout.php`, { 
        method : "POST",
        credentials: "include" 
    })
    .then(res => res.json())
    .then( data =>{
        console.log(data);
        window.location = "../../../src/pages/auth/login.html"
    })
}