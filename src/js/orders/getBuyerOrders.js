
const CONTENT_LIMIT = 10

const params = new URLSearchParams(window.location.search);
let PAGE = params.get('page') || 1


//get seller orders on document loaded
document.addEventListener("DOMContentLoaded", async () => {

    const params = new URLSearchParams(window.location.search);
    let PAGE = params.get('page') || 1

    await loadData(PAGE);
})



const IMAGES_URL = "https://objectstorage.af-johannesburg-1.oraclecloud.com/p/3kQ3VihiZsLHTaE84R5x9JR4_5tT-k_9Pp83UXk-fAX_O4I5jQFwlNcKoBZQsCa4/n/axjuhqtdpgv3/b/easymarketBUcket/o/products";


async function loadData(PAGE) {

    fetch(`/api/orders/BuyerOrders.php`, {
        method: "POST",
        credentials: "include",
        body: JSON.stringify({
            PAGE,
        })
    })
        .then(res => res.json())
        .then(data => {
            console.log(data);
            if (data.success === true && data?.orders?.length > 0) {
                //disable the next page button when less than CONTENT_LIMIT received
                if (data.orders.length >= CONTENT_LIMIT) {
                    btnNext.disabled = false
                } else {
                    btnNext.disabled = true
                }

                if (PAGE > 1) {
                    btnPrev.disabled = false
                }

                //render data
                renderOrders(data.orders)

            } else {
                //disable next btn when an emty page was found
                btnNext.disabled = true
                if (PAGE > 1) {
                    btnPrev.disabled = false
                }
            }

        }).catch(err => {
            console.log(err);
        })
}



async function renderOrders(orders) {
    console.log(orders);

    if (orders && orders.length > 0) {

        const tableData = document.getElementById("tableData")

        tableData.innerHTML = "";

        orders.forEach(order => {
            tableData.innerHTML +=
                `
                <tr onclick="openOrder(${order.orderID})" style="cursor:pointer;">
                    <td>#${order.orderID}</td>
                    <td>
                        <strong>${order.fName}, ${order.lName}</strong><br>
                        <small class="text-muted">${order.email}</small>
                    </td>
                    <td>${order.totalPrice}</td>
                    <td>${order.created_at}</td>
                    <td>
                        ${setStatus(order.status)}
                        
                    </td>
                   
                </tr>
            `
        });


    } else {
        console.log("no orders received");
        return false
    }

}




function setStatus(status) {

    const statusMap = {
        "Awaiting Payment": {
            class: "bg-warning text-dark rounded-pill px-3 py-2",
            icon: "bi-hourglass-split"
        },
        "Payment Received": {
            class: "bg-info text-dark rounded-pill px-3 py-2",
            icon: "bi-credit-card"
        },
        "Processing": {
            class: "bg-primary rounded-pill px-3 py-2",
            icon: "bi-box-seam"
        },
        "Out For Delivery": {
            class: "bg-secondary rounded-pill px-3 py-2",
            icon: "bi-truck"
        },
        "Completed": {
            class: "bg-success rounded-pill px-3 py-2",
            icon: "bi-check-circle"
        },
        "Canceled": {
            class: "bg-danger rounded-pill px-3 py-2",
            icon: "bi-x-circle"
        }
    };

    const s = statusMap[status];

    if (!s) {
        return `
            <span class="badge bg-dark rounded-pill px-3 py-2">
                <i class="bi bi-question-circle me-1"></i>
                Pending
            </span>
        `;
    }

    return `
        <span class="badge ${s.class}">
            <i class="bi ${s.icon} me-1"></i>
            ${status}
        </span>
    `;
}


const btnNext = document.getElementById("btnNextPage")
const btnPrev = document.getElementById("btnPrevPage")

btnNext.addEventListener("click", () => {
    PAGE++
    window.location = `${window.location.pathname}?page=${PAGE}`
})

btnPrev.addEventListener("click", () => {
    //prevent from going less than 0
    if (PAGE <= 1) {
        PAGE = 1
        btnPrev.disabled = true
    } else {
        PAGE--
    }
    window.location = `${window.location.pathname}?page=${PAGE}`
})




