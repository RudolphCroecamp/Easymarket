import {BACKEND_URL, IMAGES_URL} from "../config.js"

//handle errors
import {showToast} from "../toast.js"
import {setErrorMessage, hideErrorMessage} from "../handleErrorMessage.js"

import getComments from "../comments/getComments.js"
import newComment from "../comments/newComment.js"

import {sendMessage} from "../improved_messages/newMessage.js"
import {getMessagesForChat, renderChatMessages} from "../improved_messages/getMessagesByGroupID.js"

// import {createPaymentRequest} from "../payment/payments.js"

let loading = false;

let page = 1

const container = document.getElementById("productview-container");
// const loadingText = document.getElementById("loading");


const upvotes_img = "../../images/upvotes.png"
const downvotes_img= "../../images/downvotes.png"





document.addEventListener("DOMContentLoaded", ()=>{
    const searchBtn = document.getElementById("searchBtn")
    let AMOUNT = 1;
    let amountAvailable = 0;

    searchBtn.addEventListener("click", ()=>{
        const query = document.getElementById("searchBox").value
        
        //check that we have a value before making request to server
        if(!query || query.length === 0){
            console.log({"error" : "invalid search term"});
            return false;
        }

        window.location = `./search-view.html?query=${query}` 
    })

    loadProducts()

})





async function loadProducts(){
    //prevent the client from making unwanted request
    if(loading) return;

    loading = true;

    let params = new URLSearchParams(location.search);
    const productID = params.get('productID')


    //get products
    fetch(`${BACKEND_URL}/products/getProductById.php?productID=${productID}`, {
        method : "POST",
        credentials : "include"
    })
    .then(res => res.json())
    .then(async data => {

        if(data.success===false){
            //log error on failed
            console.log(productID);
            throw new Error(data.error); 
        }else{
            console.log(data);

            const product = data.product

            console.log(product);

            const sellerSince_year = new Date(product.created_at).getFullYear();
            const sellerSince_month = getMonthFromInt(new Date(product.created_at).getMonth())


            let listingPrice = parseFloat(product.price);

            if (isNaN(listingPrice)) {
                listingPrice = 0;
            }else{
                listingPrice = listingPrice.toFixed(2);  
            }

            //add product into container
            container.innerHTML += 
            `
                <div class="row justify-content-center g-4">

                    <!-- Images -->
                    <div class="col-12 col-lg-8">
                        <div class="card shadow-sm border-0 p-3">

                            <!-- Main Image -->
                            <div class="mb-3 text-center">
                                <img id="mainImage"
                                    class="img-fluid rounded"
                                    style="max-height: 400px; object-fit: contain;">
                            </div>

                            <!-- Thumbnails -->
                            <div class="row g-2" id="thumbnailContainer"></div>

                        </div>
                    </div>


                    <!-- Seller Details -->
                    <div class="col-12 col-lg-4">
                        <div class="card shadow-sm border-0 p-3 h-100">
                            <h3 class="mb-3">Seller Information</h3>

                            <p><strong>Name:</strong> ${product.fname} ${product.lname}</p>

                            <p><strong>Selling since:</strong> ${sellerSince_month}, ${sellerSince_year}</p>

                            <p class="d-flex align-items-center gap-2">
                                <strong>Rating:</strong> 
                                <img src="${upvotes_img}" class="icon"/>
                                ${product.upvotes}
                                <img src="${downvotes_img}" class="icon"/>
                                ${product.downvotes}
                            </p>

                            <button class="btn btn-success" id=reviewProduct data-bs-toggle="modal" data-bs-target="#reviewModal">
                                review
                            </button>


                            <!-- message box -->
                            <div class="mt-3">

                                <!-- Divider -->
                                <hr>

                                <h6 class="mb-2">Contact Seller</h6>

                                <!-- Messages container -->
                                <div class="message-container mb-3 p-2 rounded bg-light"  
                                    style="max-height: 200px; overflow-y: scroll;" id="message_container">
                                    <!-- messsages are added here-->
                                </div>

                                <!-- Input + Send -->
                                <div class="input-group">
                                    <input type="text" 
                                        class="form-control" 
                                        placeholder="Type a message..."
                                        id="messageInput">

                                    <button class="btn btn-success" id="sendMessageBtn">
                                        Send
                                    </button>
                                </div>

                            </div>
                        </div>  
                    </div>

                    <!-- Product Details -->

                    <!-- General Details -->
                    <div class="col-12">
                        <div class="card shadow-sm border-0 p-3">

                            <div class="d-flex flex-wrap gap-3 text-muted small fw-semibold">
                                <h2 class="fw-bold text-primary mb-3">
                                    R ${Number(product.price).toFixed(2)}
                                </h2>
                            </div>

                            <div class="d-flex flex-wrap gap-3 text-muted small fw-semibold">
                                <span class="badge bg-light text-dark">
                                    ⏱ ${timeAgo(product.created_at)}
                                </span>

                                <span class="badge bg-light text-dark">
                                    👁 ${product.views || 16}
                                </span>

                                <span class="badge bg-light text-dark" id="setLocation">
                                    📍 ${product.province + ", " + product.city || "Port Elizabeth, Eastern Cape"}
                                </span>

                            </div>

                            <div class="d-flex flex-wrap gap-3 text-muted small fw-semibold mt-3">
                                <button type="submit" class="btn btn-success" id="orderProduct">Order Now</button>
                            </div>

                        </div>
                    </div>
                    
                    <!-- Description -->
                    <div class="col-12 col-lg-12">
                        <div class="card shadow-sm border-0 p-3 h-100">
                            <h3 class="fw-bold">Description</h3>
                            <p class="text-muted">${product.description || ""}</p>
                        </div>
                    </div>

                    

                </div>
            `;


            const mainImage = document.getElementById("mainImage");
            const imgcontainer = document.getElementById("thumbnailContainer");

            const images = []

            for (let i = 0; i < product.imageCount; i++) {
                images.push(`${IMAGES_URL}/${product.images[i]}`);
            }

            // set first image
            mainImage.src = images[0];

            // create thumbnails
            images.forEach((imgPath) => {
                const col = document.createElement("div");
                col.className = "col-3";

                const img = document.createElement("img");
                img.src = imgPath;
                img.className = "img-fluid rounded border";
                img.style.height = "80px";
                img.style.objectFit = "cover";
                img.style.cursor = "pointer";

                img.addEventListener("click", () => {
                    mainImage.src = img.src;
                });

                col.appendChild(img);
                imgcontainer.appendChild(col);
            });

            //add event for ordering product
            document.getElementById("orderProduct").addEventListener("click", async ()=>{
                window.location.href = `/src/pages/payments/payment.html?productID=${productID}`
            })

            //get similar products
            await getSimilarProducts(product.category)

            loadComments(productID, page=1)


            //send message to seller
            document.getElementById("sendMessageBtn").addEventListener("click", async () => {
                console.log("sending message");

                const messageInput = document.getElementById("messageInput");
                const container = document.getElementById("message_container");

                if (!messageInput.value.trim()) return;

                const message = messageInput.value

                //send message to db
                try {
                    await sendMessage(productID, 0, message, container);
                } catch (error) {
                    console.log(error);
                    showToast(error)
                }
                

                messageInput.value = "";
            });

            try {
                await renderChatMessages(productID)
                const message_container = document.getElementById("message_container")
                message_container.scrollTop = message_container.scrollHeight;
            } catch (error) {
                const messageInput = document.getElementById("messageInput")
                messageInput.value = error
                messageInput.disabled = true;

                document.getElementById("sendMessageBtn").disabled = true;
            }


                
            
            loading = false;
        }

    }).catch(error =>{
        console.log(error)
        //show error message to client
        setErrorMessage(error)
    });
}





async function getSimilarProducts(category){
    fetch(`${BACKEND_URL}/products/similarProducts.php?category=${category}`, {
        method : "POST",
        credentials : "include"
    })
    .then(res => res.json())
    .then(data =>{
        console.log(data);

        //load similar products
        if(data.success === true){
            renderSimilarProducts(data.products)   
        }else{
            throw new Error(data.error || "No similar products found");
        }
        
    })
    .catch(error =>{
        console.log(error)
        //show error message to client
        console.log(error);
        showToast(error)
    })
}



const slider = document.getElementById("similarProductsContainer");

document.getElementById("scrollLeft").addEventListener("click", () => {
    slider.scrollBy({
        left: -250,
        behavior: "smooth"
    });
});

document.getElementById("scrollRight").addEventListener("click", () => {
    slider.scrollBy({
        left: 250,
        behavior: "smooth"
    });
});


function renderSimilarProducts(products) {
    //prevent if no products was passed
    if(!products || products.length <=0){
        return
    }

    console.log(products);

    const container = document.getElementById("similarProductsContainer");
    container.innerHTML = "";

    products.forEach(product => {
        container.innerHTML += `
            <div class="card slider-card border-0 shadow-sm">

                <div class="p-2 text-center">
                    <img src="${IMAGES_URL}/${product.productID}_a.jpg"
                         class="img-fluid"
                         style="height:120px; object-fit:contain;">
                </div>

                <div class="card-body d-flex flex-column p-2">

                    <h6 class="text-truncate mb-1">
                        ${product.name}
                    </h6>

                    <p class="text-muted small mb-2">
                        R${product.price}
                    </p>

                    <a href="./product-view.html?productID=${product.productID}"
                       class="btn btn-sm btn-dark mt-auto w-100">
                        View
                    </a>

                </div>

            </div>
        `;
    });
}



//handle reviews
let selectedRating = 0;

const stars = document.querySelectorAll("#starRating i");

stars.forEach(star => {
    star.addEventListener("click", () => {
        selectedRating = star.getAttribute("data-value");

        stars.forEach(s => s.classList.remove("active"));

        for (let i = 0; i < selectedRating; i++) {
            stars[i].classList.add("active");
        }
    });
});


document.getElementById("submitReview").addEventListener("click", async() => {
    const reviewText = document.getElementById("reviewText").value;

    if (selectedRating === 0) {
        showToast("Please select a rating ⭐");
        return;
    }

    if (!reviewText.trim()) {
        showToast("Please write a review ✍️");
        return;
    }

    const params = new URLSearchParams(window.location.search);
    const productID = params.get("productID");

    const reviewData = {
        productID : productID,
        rating : Number(selectedRating),
        message : reviewText
    };

    console.log("Submitting review:", reviewData);

    try {
        await newComment(reviewData)
        showToast("Review submitted successfully ✅", "success");

        //reset form
        selectedRating = 0;
        document.getElementById("reviewText").value = "";
        document.querySelectorAll("#starRating i").forEach(s => s.classList.remove("active"));

        //close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById("reviewModal"));
        modal.hide();
    } catch (error) {
        showToast(error)
    }
    
    
});






async function loadComments(productID, page=1){
    try {
        const data = await getComments(productID, page)

        console.log(data.comments);

        if (data.success == false) {
            throw new Error(data.error || "This product has no reviews")
        }

        const commentsContainer = document.getElementById("commentsContainer")

        data.comments.forEach(comment =>{
            commentsContainer.innerHTML += 
            `
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0 fw-semibold">${comment.fname} ${comment.lname}</h6>
                            <span class="text-warning">${createRating(comment.rating)}</span>
                        </div>
                        <p class="text-muted small mb-0">
                            ${comment.comment}
                        </p>
                    </div>
                </div>
            `
        })
    } catch (error) {
        console.log(error);
    }
}

function createRating(rating){
    let stars = ""
    for (let i = 0; i < rating; i++) {
        stars += "⭐"
    }

    return stars
}


function timeAgo(dateString) {
    const now = new Date();
    const created = new Date(dateString);
    const seconds = Math.floor((now - created) / 1000);

    const intervals = [
        { label: "year", seconds: 31536000 },
        { label: "month", seconds: 2592000 },
        { label: "day", seconds: 86400 },
        { label: "hour", seconds: 3600 },
        { label: "minute", seconds: 60 },
        { label: "second", seconds: 1 }
    ];

    for (let i of intervals) {
        const count = Math.floor(seconds / i.seconds);
        if (count > 0) {
            return `${count} ${i.label}${count > 1 ? "s" : ""} ago`;
        }
    }

    return "just now";
}


function getMonthFromInt(month_num) {
    switch (month_num) {
        case 1: return "January";
        case 2: return "February";
        case 3: return "March";
        case 4: return "April";
        case 5: return "May";
        case 6: return "June";
        case 7: return "July";
        case 8: return "August";
        case 9: return "September";
        case 10: return "October";
        case 11: return "November";
        case 12: return "December";
        default: return "Invalid month";
    }
}









