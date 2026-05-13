let LOADING = false

const params = new URLSearchParams(window.location.search);
let PAGE = params.get('page') || 1

const CONTENT_LIMIT = 10

//load table
document.addEventListener("DOMContentLoaded", ()=>{

    const UsersTable = document.getElementById("UsersTable")
    GenerateUsersTable()
})



async function GenerateUsersTable() {
    
    if(LOADING === true) return

    LOADING = true

    try {
        //get data from api
        const users = await fetchUsers(PAGE);

        //populate table
        await populateUsersTable(users)

        PAGE++

    } catch (error) {
        console.log(error);
    }
    
    LOADING = false
}


const btnNext = document.getElementById("btnNextPage")
const btnPrev = document.getElementById("btnPrevPage")

btnNext.addEventListener("click", ()=>{
    PAGE++
    window.location = `${window.location.pathname}?page=${PAGE}`
})

btnPrev.addEventListener("click", ()=>{
    //prevent from going less than 0
    if(PAGE <= 1){
        PAGE = 1
        btnPrev.disabled = true
    }else{
        PAGE--
    }
    window.location = `${window.location.pathname}?page=${PAGE}` 
})






async function fetchUsers(page){

    return new Promise(async (resolve, reject)=>{
        const response = await fetch(`/api/Admin/getUsers.php`, {
            method : "POST",
            credentials : "include",
            body : JSON.stringify({page : page})
        })
        .then(data => data.json())
        .then(data => {
            console.log(data);
            if(data.success === true && data?.users?.length > 0){
                //users found

                //disable the next page button when less than CONTENT_LIMIT received
                if(data.users.length >= CONTENT_LIMIT){
                    btnNext.disabled = false
                }else{
                    btnNext.disabled = true
                }
                
                if(PAGE > 1){
                    btnPrev.disabled = false
                }

                resolve(data.users)
            }
            //error when fecthing data
            throw new Error(data.error || "Could not find users");
        })
        .catch(err => {
            //default
            reject({
                "error" : err || "Failed to load users",
                "success" : false
            }); 
        })
    })

}






async function populateUsersTable(users){

    try {
        console.log(users);
        // return
        //clear table
        UsersTable.innerHTML="";

        //add users
        let htmlElements = ""

        users.forEach(user => {
            htmlElements += 
            `
            <tr>

                <td>
                    <div class="d-flex align-items-center gap-3">

                        <div class="user-avatar">
                            ${user.fName[0]}${user.lName[0]}
                        </div>

                        <div>
                            <div class="fw-semibold">
                            ${user.fName}, ${user.lName}
                            </div>

                            <div class="small text-muted">
                                ${user.email}
                            </div>
                        </div>

                    </div>
                </td>

                <td>${user.email}</td>

                <td>${user.city}, ${user.province}</td>

                ${user.Rating > 0 ? `<td class="text-success">${user.Rating}</td>` : `<td class="text-danger">${user.Rating || 0}</td>`}

                <td>

                    <div class="d-flex justify-content-end gap-2">

                        <button class="action-btn">
                            <i class="bi bi-eye"></i>
                        </button>

                        <button class="action-btn">
                            <i class="bi bi-pencil"></i>
                        </button>

                        <button class="action-btn">
                            <i class="bi bi-trash"></i>
                        </button>

                    </div>

                </td>

            </tr>
            `

        });

        UsersTable.innerHTML += htmlElements;

    } catch (error) {
        throw error
    }

}
