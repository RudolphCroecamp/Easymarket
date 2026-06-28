import {BACKEND_URL} from "../config.js"

//call this method when window loads
window.addEventListener("load", ()=>{
    isAuthed()
})


//check if user is authed on backend
//redirect them back to login page
export default async function isAuthed(){
    //get current pathname of client
    const currentPath = window.location.pathname

    fetch(`${BACKEND_URL}/Auth/isAuthed.php`, { 
        method : "POST",
        credentials: "include" 
    })
    .then(res => res.json())
    .then(data =>{
        console.log(data);
        //determin if user is authenticated
        if(data.logged_in === true){
            //cookies should be set by server
            return true
        }else{
            //determin if user should be redirected
            if (currentPath.endsWith('login.html')){
                //do no redirect user to login page if they are already on the login page
                return false;
            } else {
                //redirect user to login page if they arnt logged in already and not on login page
                //window.location = "/src/pages/auth/login.html"
                return false;
            }
        }
    })
}