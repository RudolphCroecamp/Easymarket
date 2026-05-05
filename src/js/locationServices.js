
export {getUserLocation, getLocationFromGPS, searchAddresses}


//get and return the users latitude and longitude
function getUserLocation() {
    return new Promise((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                resolve({
                    lat: lat,
                    lng: lng
                });
            },
            (error) => {
                reject(error);
            },
            {
                enableHighAccuracy: true,
                timeout: 5000,
                maximumAge: 0
            }
        );
    });
}





    
async function getLocationFromGPS() {
    return new Promise((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(async (pos) => {
            try {
                const lat = parseFloat((pos.coords.latitude).toFixed(2))
                const long = parseFloat((pos.coords.longitude).toFixed(2))

                const res = await fetch(
                    `https://nominatim.openstreetmap.org/reverse?format=json&lat=${pos.coords.latitude}&lon=${pos.coords.longitude}`
                );

                const data = await res.json();

                console.log(data);

                const province = data.address.state || "Unknown";

                const city =
                    data.address.city ||
                    data.address.town ||
                    data.address.village ||
                    "Unknown";

                console.log(province, city);

                resolve({
                    "success" : true,
                    "province" : province, 
                    "city" : city, 
                    "lat" : lat, 
                    "long" : long 
                });
            } catch (err) {
                reject(err);
            }
        }, (err) => {
            console.log(err);

            //default
            reject({
                "error" : "Failed to load coordinates",
                "success" : false
            }); 
        });
    });
}





async function searchAddresses(query) {
    if (!query || query.length < 3) return [];

    const url = `https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=10&countrycodes=za&viewbox=16.3,-22.1,32.9,-34.8&bounded=1&q=${encodeURIComponent(query)}`;

    try {
        const res = await fetch(url, {
            headers: {
                "User-Agent": "mappsie/1.0"
            }
        });

        const data = await res.json();
        return data;
    } catch (err) {
        console.error("Address search failed:", err);
        return [];
    }
}




