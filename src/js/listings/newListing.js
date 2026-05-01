import {BACKEND_URL} from "../config.js"

//handle errors
import {showToast} from "../toast.js"
import {setErrorMessage, hideErrorMessage} from "../handleErrorMessage.js"

import {categoryOptions} from "./listingOptions.js"

const dropArea = document.getElementById('drop-area');
const fileInput = document.getElementById('images');
const preview = document.getElementById('preview');

const categoryContainer = document.getElementById("category")
const conditionContainer = document.getElementById("condition")
const deliveryContainer = document.getElementById("delivery")


const locationData = await loadLocationData()
const categoryData = await loadCategoryData()


let filesToUpload = [];

//Click drop area to open file selector
dropArea.addEventListener('click', () => fileInput.click());

//Handle file input
fileInput.addEventListener('change', () => addFiles(fileInput.files));

//Drag and drop events
dropArea.addEventListener('dragover', e => {
    e.preventDefault();
    dropArea.classList.add('dragover');
});
dropArea.addEventListener('dragleave', () => dropArea.classList.remove('dragover'));
dropArea.addEventListener('drop', e => {
    e.preventDefault();
    dropArea.classList.remove('dragover');
    addFiles(e.dataTransfer.files);
});


// Function to add files and show previews
function addFiles(files) {
    for (let file of files) {
        if (!file.type.startsWith('image/')) continue;

        const index = filesToUpload.length;
        filesToUpload.push(file);

        const reader = new FileReader();
        reader.onload = e => {
            // wrapper div
            const wrapper = document.createElement('div');
            wrapper.classList.add('preview-item');

            // image
            const img = document.createElement('img');
            img.src = e.target.result;

            // remove button
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button'; // important!
            removeBtn.classList.add('remove-btn');
            removeBtn.innerHTML = '&times;';
            removeBtn.addEventListener('click', (event) => {
                event.stopPropagation(); // prevent triggering drop-area click
                filesToUpload.splice(index, 1);
                wrapper.remove();
            });

            wrapper.appendChild(img);
            wrapper.appendChild(removeBtn);
            preview.appendChild(wrapper);
        };
        reader.readAsDataURL(file);
    }
}



//handle listing details upload
const form = document.getElementById('newLisitingForm');

form.addEventListener('submit', async e => {
    e.preventDefault();
    hideErrorMessage()

    const title = document.getElementById("title")
    const price = document.getElementById("price")
    const condition = document.getElementById("condition")
    const description = document.getElementById("description")
    const delivery = document.getElementById("delivery")//delivery method
    const province = document.getElementById("province")
    const city = document.getElementById("city")
    const category = document.getElementById("category")
    const subcategorySelect = document.getElementById("subcategorySelect")

    if(
        validateInput(title, price, condition, description, delivery, province, city, category, subcategorySelect)
    ){
        const formData = new FormData();

        // append only dragged/selected files manually
        filesToUpload.forEach(file => formData.append('images[]', file));

        // append all other fields manually
        formData.append("title", title.value);
        formData.append("price", price.value);
        formData.append("category", categoryContainer.value);//default other
        formData.append("condition", conditionContainer.value);//default new
        formData.append("description", description.value);
        formData.append("delivery", deliveryContainer.value);
        formData.append("province", province.value);
        formData.append("city", city.value);
        formData.append("category", category.value);
        formData.append("subcategory", subcategorySelect.value);

        //get tags from tagsInput
        const tagsArray = tagify.value.map(tag => tag.value);


        //tags could be empty becasue it is optional
        if(tagsArray.length > 0) formData.append("tags", JSON.stringify(tagsArray));
        

        try {
            fetch(`${BACKEND_URL}/listings/newListing.php`, {
                method: 'POST',
                credentials : "include",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                console.log(data);

                //redirect back to home screen or show error
                if(data.success === true){
                    showToast(data.message, "success")
                    window.location = "/"
                    form.reset()
                }else{
                    setErrorMessage(data.error)
                    showToast(data.message || data.error, "warning")
                }
            })

        } catch (err) {
            console.error(err);
        }

    }

    
});


//reset page and redirect back to home screen
form.addEventListener("reset", ()=>{
    window.location = "/"
})




//validate an array of inputs
//valid if it has a value 
function validateInput(...elements){
    
    for (let elm of elements) {
        if(elm.tagName.toLowerCase() === "input" || elm.tagName.toLowerCase() === "textarea"){//check input and textarea elements for value
            if(!elm.value || elm.value.trim().length === 0){
                elm.focus()
                showToast("Fill in all fields");
                return false;
            }
        }else if(elm.tagName.toLowerCase() === "select"){//check select elements for a value
            //invalid value if index == 0
            if(elm.selectedIndex == 0){
                elm.focus()
                showToast("Fill in all fields");
                return false;
            }  
        }else{
            //error if we did not check all the input tags
            showToast("Unknown validation error");
            return false;
        } 
    }
    return true;
}


// Initialize Tagify
const input = document.getElementById("tagsInput");

const tagify = new Tagify(input, {
    delimiters: ",| ", // Enter, comma, or space
    maxTags: 10,
    whitelist: [], // optional: predefined suggestions
    dropdown: {
        enabled: 0
    }
});




//append categories to category dropdown container / select element
const categorySelect = document.getElementById("category")
const subcategorySelect = document.getElementById("subcategorySelect")

//add main categories
categoryData.categories.forEach((mainCat, index)=>{
    const option = document.createElement("option");
    option.value = mainCat.name;
    option.textContent = mainCat.name;
    categorySelect.appendChild(option);
})



categorySelect.addEventListener("change", () => {
    const categorySelectText = categorySelect.value

    //disable subcategory select element
    if(categorySelectText == "0"){
        //reset to defaults when no option was selected
        subcategorySelect.disabled = true;
        subcategorySelect.selectedIndex = 0;
        return
    }

    //enable subcategory select element
    subcategorySelect.disabled = false;


    const selected = categoryData.categories.find(
        c => c.name === categorySelectText
    );

    subcategorySelect.innerHTML = "";

    subcategorySelect.innerHTML += "<option value='0' selected>Select a subcategory</option>";

    selected.subcategories.forEach(sub => {
        const option = document.createElement("option");
        option.value = sub;
        option.textContent = sub;
        subcategorySelect.appendChild(option);
    });
});




//append available location to container
const provinceSelect = document.getElementById("province");
const citySelect = document.getElementById("city");


//add provices
locationData.provinces.forEach((name)=>{
    const option = document.createElement("option");
    option.value = name.name;
    option.textContent = name.name;
    provinceSelect.appendChild(option);
})


provinceSelect.addEventListener("change", async () => {
    //disable subcategory select element
    if(provinceSelect.value == "0"){
        //reset to defaults when no option was selected
        citySelect.disabled = true;
        citySelect.selectedIndex = 0;
        return
    }

    //enable city select element
    citySelect.disabled = false;

    const selected = locationData.provinces.find(p => p.name === provinceSelect.value)

    citySelect.innerHTML = "";

    citySelect.innerHTML += "<option value='0' selected>Select a city</option>";

    selected.cities.forEach(city => {
        const option = document.createElement("option");
        option.value = city.name;
        option.textContent = city.alt 
        ? `${city.name} (${city.alt})` 
        : city.name;

        citySelect.appendChild(option);
    });
});


//get data from locations.json
async function loadLocationData() {
  try {
    const response = await fetch("../../js/listings/locations.json");
    const data = await response.json();

    console.log(data);

    return data;
  } catch (err) {
    console.error(err);
  }
}

//get data from categoryOptions.json
async function loadCategoryData() {
  try {
    const response = await fetch("../../js/listings/categoryOptions.json");
    const data = await response.json();

    console.log(data);

    return data;
  } catch (err) {
    console.error(err);
  }
}