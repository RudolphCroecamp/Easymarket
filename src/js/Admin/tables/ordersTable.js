let LOADING = false

const params = new URLSearchParams(window.location.search);
let PAGE = Number(params.get('page')) || 1

const CONTENT_LIMIT = 10

//load table
document.addEventListener("DOMContentLoaded", ()=>{

    const OrdersTable = document.getElementById("OrdersTable")

    GenerateOrdersTable()
})



async function GenerateOrdersTable() {
    
    if(LOADING === true) return

    LOADING = true

    try {
        //get data from api
        const orders = await fetchOrders(PAGE);

        //populate table
        await populateOrdersTable(orders)

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






async function fetchOrders(page){

    return new Promise(async (resolve, reject)=>{
        const response = await fetch(`/api/Admin/getOrders.php`, {
            method : "POST",
            credentials : "include",
            body : JSON.stringify({page : page})
        })
        .then(data => data.json())
        .then(data => {
            console.log(data);
            if(data.success === true && data?.orders?.length > 0){
                //users found

                //disable the next page button when less than CONTENT_LIMIT received
                if(data.orders.length >= CONTENT_LIMIT){
                    btnNext.disabled = false
                }else{
                    btnNext.disabled = true
                }
                
                if(PAGE > 1){
                    btnPrev.disabled = false
                }

                resolve(data.orders)
            }else{
                //disable next btn when an emty page was found
                btnNext.disabled = true
                if(PAGE > 1){
                    btnPrev.disabled = false
                }

                //error when fecthing data
                throw new Error(data.error || "Could not find orders");
            }

            
        })
        .catch(err => {
            //default
            reject({
                "error" : err || "Failed to load orders",
                "success" : false
            }); 
        })
    })

}






async function populateOrdersTable(orders){

    try {
        console.log(orders);
        // return
        //clear table
        OrdersTable.innerHTML="";

        //add users
        let htmlElements = ""

        orders.forEach(order => {
            htmlElements += 
            `
            <tr>
                <td>#${order.paymentID}</td>

                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="stats-icon bg-primary-subtle text-primary" style="width:40px;height:40px;font-size:0.9rem;">
                            <i class="bi bi-person"></i>
                        </div>
                        ${order.fName} ${order.lName}
                    </div>
                </td>

                <td>
                    ${order.price}
                </td>

                <td>
                    ${(() => {
                        const statusMap = {
                            "Awaiting Payment": "bg-warning text-white",
                            "Payment Received": "bg-info text-white",
                            "Out for Delivery": "bg-primary text-white",
                            "Completed": "bg-success text-white"
                        };

                        const badgeClass = statusMap[order.status] || "bg-secondary text-white";

                        return `<span class="stock-badge ${badgeClass}">
                                    ${order.status}
                                </span>`;
                    })()}
                </td>

                <td>
                    ${order.created_at}
                </td>

                

                <td class="text-end">
                    <button class="action-btn">
                        <i class="bi bi-eye"></i>
                    </button>

                    <button class="action-btn">
                        <i class="bi bi-check2"></i>
                    </button>

                    <button class="action-btn">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
            `

        });

        OrdersTable.innerHTML += htmlElements;

    } catch (error) {
        throw error
    }

}
