export {ErrorMessage_init, setErrorMessage, hideErrorMessage}

//initialise error message  container
ErrorMessage_init()


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


function ErrorMessage_init(){
    //append error message container to body
    document.body.innerHTML += 
    `
        <!-- error messages are added in this container -->
        <div class="bg-danger-subtle visually-hidden" id="error-box">
            <p id="error-message" class="text-body p-2"></p>
        </div>
    `;
}