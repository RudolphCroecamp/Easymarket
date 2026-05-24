import Cart from "./Cart.js";
import {BACKEND_URL, IMAGES_URL} from "../config.js"


const shippingFee = 150
const platformFee = 5 //percentage



document.addEventListener("DOMContentLoaded", async () => {
    const cartItems = await getCartItems();
    recalc(cartItems)

    await renderCartItems(cartItems)

    document.getElementById("checkoutBtn").addEventListener("click",()=>{
        window.location = "/src/pages/payments/payment.html"
    })

})



function fmt(n) { return 'R ' + Math.round(n).toLocaleString('en-ZA'); }

function recalc(cartItems) {

    const totaCartPrice = cartItems.reduce((accumulator, item) => {
        return accumulator + Number(item.price) * Number(item.quantity);
    }, 0);

    const itemsToShip = cartItems.reduce((acc, item) => {
        return acc + Number(item.quantity)
    }, 0)

    const ShippingTotal = itemsToShip * shippingFee

    const tototalPrice = (totaCartPrice + ShippingTotal) * (platformFee + 100) / 100

    console.log(totaCartPrice);
    console.log(ShippingTotal);
    console.log(tototalPrice * platformFee / 100);

    document.getElementById('sum-subtotal').textContent = fmt(totaCartPrice);
    document.getElementById('sum-shipping').textContent = fmt(ShippingTotal);
    document.getElementById('sum-vat').textContent = fmt(tototalPrice * platformFee / 100);
    document.getElementById('sum-total').textContent = fmt(tototalPrice);

}




async function getCartItems() {
    const userCart = new Cart()
    userCart.init()
    const cartItems = await userCart.getCartItems()
    console.log(cartItems);
    return cartItems
}

async function removeItem(id) {
    const userCart = new Cart()
    userCart.init()
    const cartItems = await userCart.removeItem(id)
}

async function changeQty(id, amount) {
    console.log("Calling cart quantity update");
    const userCart = new Cart()
    userCart.init()
    await userCart.updateQuantity(id, amount)
    const cartItems = await userCart.getCartItems()

    recalc(cartItems);

    
}





async function renderCartItems(cartItems) {

    const container = document.getElementById("cart-list")

    container.innerHTML = ""

    cartItems.forEach(item => {
        container.innerHTML += 
        `
        <div class="cart-row"
            data-id="${item.productID}"
            data-price="${item.price}">

            <!-- IMAGE -->
            <div class="prod-img">
                <img 
                    src="${IMAGES_URL}/${item.productID}_a.webp"
                    alt="${item.name}"
                    onerror="this.onerror=null;this.src='/src/images/placeholder.png';"
                >
            </div>

            <!-- INFO -->
            <div class="prod-info">
                <p class="prod-name mb-1">
                    ${item.name}
                </p>

                <p class="prod-meta mb-0">
                    ${item.description}
                </p>
            </div>

            <!-- QTY -->
            <div class="qty-box">
                <button class="qty-btn qty-minus" data-id="${item.productID}"
                        onclick="changeQty('${item.productID}', -1)">
                    −
                </button>

                <span class="qty-val"
                    id="qty-${item.productID}">
                    ${item.quantity}
                </span>

                <button class="qty-btn qty-plus" data-id="${item.productID}"
                        onclick="changeQty('${item.productID}', 1)">
                    +
                </button>
            </div>

            <!-- PRICE -->
            <div class="prod-price">
                R ${Number(item.price).toFixed(2)}
            </div>

            <!-- REMOVE -->
            <button class="remove-btn" onclick="removeCard('${item.productID}')" aria-label="Remove item" >
                <i class="bi bi-trash3" data-id="${item.productID}"></i>
            </button>

        </div>
    `;
});


document.getElementById("cart-list").addEventListener("click", (e) => {
    console.log("clicked on cart item", e.target);
    const id = e.target.dataset.id;

    console.log(id);

    if(!id) return

    // REMOVE ITEM
    if (e.target.closest(".remove-btn")) {
        const btn = e.target.closest(".remove-btn");
        const id = e.target.dataset.id;

        console.log("removing:", id);
        removeItem(id);

        console.log("clicked on cart remove btn");
    }

    // PLUS
    if (e.target.closest(".qty-plus")) {
        const btn = e.target.closest(".qty-plus");
        changeQty(btn.dataset.id, 1);
        
        console.log("clicked on cart increment quantity", btn.dataset.id);
    }

    // MINUS
    if (e.target.closest(".qty-minus")) {
        const btn = e.target.closest(".qty-minus");
        changeQty(btn.dataset.id, -1);

        console.log("clicked on cart decrement quantity", btn.dataset.id);
        
    }
});


}//end







