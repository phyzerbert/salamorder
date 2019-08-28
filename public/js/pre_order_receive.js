var app = new Vue({
    el: '#app',

    data: {
        order_items: [],
        products: [],
        total: {
            discount: 0,
            cost: 0
        },
        params: {
            id: $('#order_id').val()
        },
        discount: 0,
        discount_string: 0,
        grand_total: 0,
    },

    methods:{
        init() {
            axios.post('/get_pre_order',this.params)
                .then(response => {
                    this.discount_string = response.data.discount_string
                    for (let i = 0; i < response.data.items.length; i++) {
                        const element = response.data.items[i];
                        axios.post('/get_product', {id:element.product_id})
                            .then(response1 => {
                                axios.post('/get_received_quantity', {id:element.id})
                                    .then(response2 => {
                                        this.order_items.push({
                                            product_id: element.product_id,
                                            product_code: response1.data.code,
                                            product_name: response1.data.name,
                                            cost: element.cost,
                                            discount: element.discount,
                                            discount_string: element.discount_string,
                                            ordered_quantity: element.quantity,
                                            received_quantity: response2.data,
                                            balance: element.quantity - response2.data,
                                            receive_quantity: element.quantity - response2.data,
                                            sub_total: element.subtotal,
                                            item_id: element.id,
                                        })
                                        console.log(this.order_items)
                                    })
                                    .catch(error => {
                                        console.log(error);
                                    });
                            })
                            .catch(error => {
                                console.log(error);
                            });                    
                    }
                })
                .catch(error => {
                    console.log(error);
                }); 
        },
        calc_subtotal() {
            data = this.order_items
            // console.log(data)
            let total_discount = 0;
            let total_cost = 0;

            for(let i = 0; i < data.length; i++) {
                this.order_items[i].sub_total = (parseInt(data[i].cost) - parseInt(data[i].discount)) * data[i].receive_quantity
                total_discount += parseInt(data[i].discount) * data[i].receive_quantity
                total_cost += data[i].sub_total
            }
            this.total.discount = total_discount
            this.total.cost = total_cost
        },
        calc_grand_total() {
            this.grand_total = this.total.cost - this.discount
        },
        calc_discount(){
            let reg_patt1 = /^\d+(?:\.\d+)?%$/
            let reg_patt2 = /^\d+$/
            if(reg_patt1.test(this.discount_string)){
                this.discount = this.total.cost*parseFloat(this.discount_string)/100
                // console.log(this.discount)
            }else if(reg_patt2.test(this.discount_string)){
                this.discount = this.discount_string
            }else if(this.discount_string == ''){
                this.discount = 0
            }else {
                this.discount_string = '0';
            }
        },
        formatPrice(value) {
            let val = value;
            return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
    },

    mounted:function() {
        this.init();
        $("#app").css('opacity', 1);
    },
    updated: function() {
        this.calc_subtotal()
        this.calc_discount()
        this.calc_grand_total()
    }    
});


