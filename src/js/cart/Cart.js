import { showToast } from "/src/js/toast.js";

export default class Cart {
    cart = []

    constructor() {
        this.cart = [];
    }


    init(){

        try {
            if (localStorage.getItem('cart') !== null) {
                // Item exists, safe to use
                this.cart = JSON.parse(localStorage.getItem('cart'));
            } else {
                localStorage.setItem("cart", JSON.stringify([]))
                this.cart = []
            }   
        } catch (error) {
            throw error
        }
        
    }

    clearCart(){
        localStorage.setItem("cart", JSON.stringify([]))
        this.cart = [];
    }

    isItemInCart(newItem){
        try {
            return this.cart.some(
                item => item.productID === newItem.productID
            );
        } catch (error) {
            throw error
        }
    }


    addItem(newItem){
        try {
            if(this.isItemInCart(newItem)) throw new Error("Item already added to cart");
            console.log(newItem);
            this.cart.push(newItem)
            localStorage.setItem('cart', JSON.stringify(this.cart));

            showToast("Item added to cart", "success")

        } catch (error) {
            throw error
        }
        
    }


    removeItem(itemID) {
        try {

            const newCart = this.cart.filter(item =>
                item.productID != itemID
            );

            this.cart = newCart;

            localStorage.setItem('cart', JSON.stringify(newCart));

            showToast("Item removed from cart", "success");

        } catch (error) {
            throw error;
        }
    }

    getCartItems(){
        return this.cart 
    }


    updateQuantity(id, amount){
        console.log("Updateing amount in cart");

        const newCart = this.cart.map(item => {

            if(item.productID === id){
                
                if(amount > 0){
                    item.quantity += 1
                }else{
                    if(item.quantity >= 2){
                        item.quantity -= 1
                    }else{
                        item.quantity = 1
                    }
                }
            }
            return item
        });
        
        console.log(newCart);

        this.cart = newCart
        localStorage.setItem('cart', JSON.stringify(newCart));
    }


    getCartTotalValue(){
        let total = 0;
        this.cart.forEach(item=>{
            const price = parseFloat(item.price) || 0;
            const amount = parseInt(item.quantity) || 0;
            total += (price * amount)
        })

        return total
    }

    calcTotal(){
        const subTotal = this.getCartTotalValue()
        const shipping = this.cart.length * 100//R100 per item
        const total = subTotal + shipping

        return [subTotal, shipping, total]
    }

}//end
