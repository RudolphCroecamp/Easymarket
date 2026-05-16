
let LOADING = false

const params = new URLSearchParams(window.location.search);
let PAGE = params.get('page') || 1

const CONTENT_LIMIT = 10



const IMAGES_URL = "https://objectstorage.af-johannesburg-1.oraclecloud.com/p/3kQ3VihiZsLHTaE84R5x9JR4_5tT-k_9Pp83UXk-fAX_O4I5jQFwlNcKoBZQsCa4/n/axjuhqtdpgv3/b/easymarketBUcket/o/products";

//load table
document.addEventListener("DOMContentLoaded", ()=>{

    const listingsTable = document.getElementById("listingsTable")
    GenerateListingsTable()
})



async function GenerateListingsTable() {
    
    if(LOADING === true) return

    LOADING = true

    try {
        //get data from api
        const listings = await fetchListings(PAGE);

        //populate table
        await populateListingsTable(listings)

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





async function fetchListings(page){

    return new Promise(async (resolve, reject)=>{
        const response = await fetch(`/api/Admin/getListings.php`, {
            method : "POST",
            credentials : "include",
            body : JSON.stringify({page : page})
        })
        .then(data => data.json())
        .then(data => {
            console.log(data);
            if(data.success === true && data?.listings?.length > 0){
                //users found

                //disable the next page button when less than CONTENT_LIMIT received
                if(data.listings.length >= CONTENT_LIMIT){
                    btnNext.disabled = false
                }else{
                    btnNext.disabled = true
                }

                if(PAGE > 1){
                    btnPrev.disabled = false
                }

                resolve(data.listings)
            }else{
                //disable next btn when an emty page was found
                btnNext.disabled = true
                if(PAGE > 1){
                    btnPrev.disabled = false
                }

                //error when fecthing data
                throw new Error(data.error || "Could not find listings");
            }
            
        })
        .catch(err => {
            //default
            reject({
                "error" : err || "Failed to load listings",
                "success" : false
            }); 
        })
    })

    

}






async function populateListingsTable(listings){

    try {
        console.log(listings);
        // return
        //clear table
        listingsTable.innerHTML="";

        //add users
        let htmlElements = ""

        listings.forEach(listing => {
            htmlElements += 
            `
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-3">
                        <img 
                            src="${IMAGES_URL}/${listing.productID}_a.webp"
                            class="product-image"
                            loading="lazy"
                        >
                        <div>

                            <div class="fw-semibold">
                                ${listing.name}
                            </div>

                        </div>
                    </div>
                </td>

                <td>${listing.category}</td>

                <td class="fw-semibold">
                    R${listing.price}
                </td>


                ${listing.sold || listing.deleted ? 
                    `<td class="text-danger">unavailable</td>`
                    :
                    `<td class="text-success">available</td>`
                }

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

        listingsTable.innerHTML += htmlElements;

    } catch (error) {
        throw error
    }

}


