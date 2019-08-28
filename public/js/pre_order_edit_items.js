
var app = new Vue({
    el: '#app',

    data: {
        order_items: [],
        products: [],
        selected_product: '',
        total: {
            discount: 0,
            cost: 0
        },        
        discount: 0,
        discount_string: 0,
        shipping: '0',
        shipping_string: '0',
        returns: 0,
        grand_total: 0,
        params: {
            id: $('#order_id').val()
        }
    },

    methods:{
        init() {
            axios.get('/get_products')
                .then(response => {
                    this.products = response.data;
                })
                .catch(error => {
                    console.log(error);
                });  
                
            axios.post('/get_pre_order',this.params)
                .then(response => {
                    this.discount_string = response.data.discount_string
                    for (let i = 0; i < response.data.items.length; i++) {
                        const element = response.data.items[i];
                        axios.post('/get_product', {id:element.product_id})
                        .then(response1 => {
                            this.order_items.push({
                                product_id: element.product_id,
                                product_name_code: response1.data.name + "(" + response1.data.code + ")",
                                cost: element.cost,
                                discount: element.discount,
                                discount_string: element.discount_string,
                                quantity: element.quantity,
                                sub_total: element.subtotal,
                                item_id: element.id,
                            })
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
        get_product(i) {
            const data = new FormData();
            data.append('id', this.order_items[i].product_id);

            axios.post('/get_product', data)
                .then(response => {
                    this.order_items[i].cost = response.data.cost
                    this.order_items[i].tax_name = response.data.tax.name
                    this.order_items[i].tax_rate = response.data.tax.rate
                    this.order_items[i].quantity = 1
                    this.order_items[i].discount_string = 0
                    this.order_items[i].sub_total = response.data.cost
                })
                .catch(error => {
                    console.log(error);
                });
        },
        add_item() {
            this.order_items.push({
                product_id: "",
                product_name_code: "",
                cost: 0,
                discount: 0,
                discount_string: 0,
                quantity: 0,
                sub_total: 0,
            })
        },
        calc_subtotal() {
            data = this.order_items
            let total_discount = 0;
            let total_cost = 0;
            let reg_patt1 = /^\d+(?:\.\d+)?%$/
            let reg_patt2 = /^\d+$/
            for(let i = 0; i < data.length; i++) {

                if(reg_patt1.test(data[i].discount_string)){
                    this.order_items[i].discount = data[i].cost*parseFloat(data[i].discount_string)/100
                    // console.log(this.discount)
                }else if(reg_patt2.test(data[i].discount_string)){
                    this.order_items[i].discount = data[i].discount_string
                }else if(data[i].discount_string == ''){
                    this.order_items[i].discount = 0
                }else {
                    this.order_items[i].discount_string = '0';
                }

                this.order_items[i].sub_total = (parseInt(data[i].cost) - parseInt(data[i].discount)) * data[i].quantity
                total_discount += parseInt(data[i].discount) * data[i].quantity
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
        },
        remove(i) {
            this.order_items.splice(i, 1)
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
