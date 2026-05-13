import {BACKEND_URL} from "../config.js"
// import isAuthed from "./isAuthed.js"

//load more products as you reach the end
window.addEventListener("load", async () => {
    // await isAuthed(window.location.pathname)

    console.log(window.location.origin)
});


document.addEventListener("DOMContentLoaded", ()=>{
    const loginForm = document.getElementById("loginForm");
    console.log(loginForm);

    loginForm.addEventListener("submit", (e) => {
        console.log("running");
        e.preventDefault();
        hideErrorMessage()

        console.log("requesting");
        const email = document.getElementById("email")
        const password = document.getElementById("password")

        if(
            validateInput(email, password) 
            && 
            validateEmail(email.value)
        )
        {
            //get data
            const formData = new FormData(loginForm)

            console.log(formData);

            //submit inputs to backend after fields have been validated
            fetch(`${BACKEND_URL}/Auth/login.php`,
                {
                    method : "POST",
                    credentials: "include",
                    body : formData,
                }
            )
            .then(res => res.text())
            .then(data =>{
                console.log(data);

                if(data.success === true){
                    //valid user credentials

                    //check if user is an ADMIN
                    if(data.publicUserInfo && data.publicUserInfo.role === "admin"){
                        window.location = "/src/pages/Admin/dashboard.html"

                    }

                    window.location = "/"

                    
                }else{
                    //invalid user credentials
                    setErrorMessage(data.error)
                }
            }).catch(error=>{
                console.log(error);
            })
        }
    });
})











//validate an array of inputs
function validateInput(...elements){
    for (let elm of elements) {
        if(!elm.value || elm.value.trim().length === 0){
            elm.focus()
            setErrorMessage("Fill in all fields");
            return false;
        }
    }
    return true;
}

//validate an email address
function validateEmail(email) {
    console.log(email);
    const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

    if(!emailPattern.test(email)){
        document.getElementById("email").focus();
        setErrorMessage("Invalid email address");
        return false;
    }

    return true;
}


//show error message to client
function setErrorMessage(error){
    document.getElementById("error-box").classList.remove("visually-hidden");
    document.getElementById("error-message").innerText = error;
}

//hide error message from client
function hideErrorMessage(){
    document.getElementById("error-box").classList.add("visually-hidden");
    document.getElementById("error-message").innerText = "";
}