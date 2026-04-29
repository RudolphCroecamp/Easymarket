
export {toast_init, showToast}

//initialise toast message container
toast_init()


function showToast(message, type = "dark") {
    const toastEl = document.getElementById("reviewToast");
    const toastMsg = document.getElementById("toastMessage");

    // Change message
    toastMsg.textContent = message;

    toastEl.className = `toast align-items-center text-bg-${type} border-0`;

    const toast = new bootstrap.Toast(toastEl);
    toast.show();
}


function toast_init(){
    //append toast message container to body
    document.body.innerHTML += 
    `
        <!-- Toast message -->
        <div class="toast-container position-fixed bottom-0 end-0 p-3">
            <div id="reviewToast" class="toast align-items-center text-bg-primary border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body" id="toastMessage">
                        <!-- message goes here -->
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        </div>
    `;
}