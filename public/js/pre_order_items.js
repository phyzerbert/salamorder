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
        grand_total: 0,
    },

    methods:{
        init() {
            // axios.get('/get_products')
            //     .then(response => {
            //         this.products = response.data;
            //     })
            //     .catch(error => {
            //         console.log(error);
            //     });
        },
        add_item() {
            axios.get('/get_first_product')
                .then(response => {
                    this.order_items.push({
                        product_id: response.data.id,
                        product_name_code: response.data.name + "(" + response.data.code + ")",
                        cost: response.data.cost,
                        discount: 0,
                        discount_string: 0,
                        quantity: 1,
                        expiry_date: "",
                        sub_total: 0,
                    })
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
        remove(i) {
            this.order_items.splice(i, 1)
        },
        formatPrice(value) {
            let val = value;
            return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
    },

    mounted:function() {
        this.init();
        this.add_item()
        $("#app").css('opacity', 1);
    },
    updated: function() {
        this.calc_subtotal()
        this.calc_discount()
        this.calc_grand_total()
        $(".product").autocomplete({
            source : function( request, response ) {
                axios.post('/get_autocomplete_products', { keyword : request.term })
                    .then(resp => {
                        // response(resp.data);
                        response(
                            $.map(resp.data, function(item) {
                                return {
                                    label: item.name + "(" + item.code + ")",
                                    value: item.name + "(" + item.code + ")",
                                    id: item.id,
                                    cost: item.cost,
                                }
                            })
                        );
                    })
                    .catch(error => {
                        console.log(error);
                    }
                );
            }, 
            minLength: 1,
            select: function( event, ui ) {
                let index = $(".product").index($(this));
                app.order_items[index].product_id = ui.item.id
                app.order_items[index].product_name_code = ui.item.label
                app.order_items[index].cost = ui.item.cost
                app.order_items[index].discount = 0
                app.order_items[index].quantity = 1
                app.order_items[index].sub_total = ui.item.cost
            }
        });
    }    
});


