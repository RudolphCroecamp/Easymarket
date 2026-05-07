
export default categoryData_init

export {loadCategoryData, popMainContiner_cat, popSubContiner_onselect__cat}

// const BASE_PATH = window.location.pathname.split("/")[1];

const mainContainer = document.getElementById("category")
const subContainer = document.getElementById("subcategory")



async function categoryData_init(){
    try {
        const categoryData = await loadCategoryData()

        if(!categoryData || !categoryData.categories){
            throw new Error("Could not load categoryData");
        }

        //we ghave data
        await popMainContiner_cat(categoryData)


        mainContainer.addEventListener("change", async () => {
            popSubContiner_onselect__cat(categoryData)
        })


    } catch (error) {
        console.log(error);
    }
    

}



//get data from categoryOptions.json
async function loadCategoryData() {
  try {
    const response = await fetch(`/src/js/loadData/categoryOptions.json`);
    const data = await response.json();

    return data;
  } catch (err) {
    console.error(err);
  }
}




//populate containers
function popMainContiner_cat(categoryData){
    console.log(categoryData);
    //add main categories
    categoryData.categories.forEach((mainCat, index)=>{
        const option = document.createElement("option");
        option.value = mainCat.name;
        option.textContent = mainCat.name;
        mainContainer.appendChild(option);
    })
}



function popSubContiner_onselect__cat(categoryData){
    //disable subcategory select element
    if(mainContainer.value == "0"){
        //reset to defaults when no option was selected
        subContainer.disabled = true;
        subContainer.selectedIndex = 0;
        return
    }

    //enable subcategory select element
    subContainer.disabled = false;

    const selected = categoryData.categories.find(c => c.name === mainContainer.value);

    subContainer.innerHTML = "";

    subContainer.innerHTML += "<option value='0' selected>Select a subcategory</option>";

    selected.subcategories.forEach(sub => {
        const option = document.createElement("option");
        option.value = sub;
        option.textContent = sub;
        subContainer.appendChild(option);
    });
}



