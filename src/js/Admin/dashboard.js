


document.addEventListener("DOMContentLoaded", function () {
    const options = {
        chart: {
            type: 'area',
            height: 320,
            toolbar: { show: false }
        },
        series: [{
            name: 'GMV',
            data: [50000, 30000, 25000, 40000, 30000, 40000, 35000]
        }],
        xaxis: {
            categories: [
                'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'
            ]
        },
        stroke: {
            curve: 'smooth'
        },
        fill: {
            type: 'gradient'
        }
    };

    gmvChart = new ApexCharts(document.querySelector("#gmvChart"), options);
    gmvChart.render();

    const retentionOptions = {
        chart: {
            type: 'bar',
            height: 320,
            toolbar: {
                show: false
            }
        },

        series: [{
            name: 'Users',
            data: [0, 0]
        }],

        xaxis: {
            categories: [
                'Repeat Buyers',
                'Repeat Sellers'
            ]
        },

        dataLabels: {
            enabled: true
        }
    };

    
    let retentionChart = new ApexCharts(document.querySelector("#retentionChart"), retentionOptions);
    retentionChart.render();


    fetch('/api/Admin/dashboard.php', {
        method: "POST",
        credentials: "include"
    })
    .then(res => res.json())
    .then(data => {

        console.log(data);

        gmvChart.updateOptions({
            series: [{
                name: 'GMV',
                data: [50000, 30000, 25000, 40000, 30000, 40000, 35000]// data.gmvTrend.data
            }],
            xaxis: {
                categories:['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']// data.gmvTrend.labels
            }
        });

        // ✅ THIS NOW WORKS
        retentionChart.updateSeries([{
            data: [
                314,//data.buyerRetention,
                256//data.sellerRetention
            ]
        }]);


        // platform stats cards
        document.getElementById("gmvValue").innerText = "R 1,245,320" // + data.gmv;
        document.getElementById("ordersValue").innerText = "8,420" //data.orders;
        document.getElementById("usersValue").innerText = "32,110" //data.activeUsers;
    });
});