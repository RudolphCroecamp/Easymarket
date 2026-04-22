
export {my_message, their_message}


//html for messages from current user
function my_message(message){ 
    return (`
        <div class="mb-2 text-end">
            <div class="small text-muted">You</div>
            <div class="p-2 bg-primary text-white rounded shadow-sm small d-inline-block">
                ${message}
            </div>
        </div>
    `)
}


//html for messages by other user
function their_message(message){
    return (`
        <div class="mb-2">
            <div class="small text-muted">Seller</div>
            <div class="p-2 bg-secondary rounded shadow-sm small d-inline-block"">
                ${message}
            </div>
        </div>
    `)
}