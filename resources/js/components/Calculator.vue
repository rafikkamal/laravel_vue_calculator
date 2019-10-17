<template>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="calculator">
                    <div class="display">
                        <p> {{ prev_display_value }} </p>
                        <p>{{ fixDisplayedEquation() }} <span v-if="display_pipe">|</span></p>
                    </div>
                    <div class="keys">
                        <button @click="keyVal('(')">(</button>
                        <button @click="keyVal(')')">)</button>
                        <button @click="keyVal('B')">B</button>
                        <button @click="keyVal('C')">C</button>

                        <button @click="keyVal('9')">9</button>
                        <button @click="keyVal('8')">8</button>
                        <button @click="keyVal('7')">7</button>
                        <button @click="keyVal('/')">/</button>

                        <button @click="keyVal('6')">6</button>
                        <button @click="keyVal('5')">5</button>
                        <button @click="keyVal('4')">4</button>
                        <button @click="keyVal('x')">x</button>

                        <button @click="keyVal('3')">3</button>
                        <button @click="keyVal('2')">2</button>
                        <button @click="keyVal('1')">1</button>
                        <button @click="keyVal('-')">-</button>

                        <button @click="keyVal('0')">0</button>
                        <button @click="keyVal('.')">.</button>
                        <button @click="keyVal('=')">=</button>
                        <button @click="keyVal('+')">+</button>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card-header">
                    <h5 class="text-center">History</h5>
                </div>
                <div class="card-body history">
                    <ul>
                        <li v-for="history_content in guest_history" >{{history_content.equation}} = {{history_content.result}}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import example_component from './ExampleComponent.vue'
import axios from 'axios'

export default {
    data: function() {
        return {
            //display_value: "8 + 3 x 2 - ( 2 - 1 ) + ( 9 / 2 + 3 )",
            display_value: "2 + ( 2 - 1 )",
            equation: "",
            result: "",
            prev_display_value: "1 + 2 + 3 - 4 = 2",
            guest_history: [],
            display_has_number_now: false,
            site_url: "http://localhost:8000",
            guest_id: 0,
            display_pipe: true
        }
    },
    components: {
        example_component
    },
    props: ["ip"],
    methods: {
        keyVal: function(val) {
            var equation = this.display_value

            /** 
             * Fix the displayed equation
             * Stop the overflow from the display screen 
             */
            this.fixDisplayedEquation();

            switch(val) {
                case "B":
                    var len = this.display_value.length
                    if(len > 1) {
                        var current_val = this.display_value[this.display_value.length-1]
                        // console.log("current_val : "+current_val)
                        var cut_length = 2
                        if(current_val==".") {
                            cut_length = 1
                        } else if(current_val>=0 && current_val<=9) {
                            if(this.hasSpaceBefore()) {
                                cut_length = 2
                            } else {
                                cut_length = 1
                            }
                        } 
                        this.display_value = this.display_value.substr(0, len - cut_length)
                    } else {
                        this.display_value = ""
                    }
                    break
                case "C":
                    this.display_value = ""
                    this.display_has_number_now = false
                    console.log("<> string length: "+this.display_value.length)
                    break
                case ".":
                    this.display_value += val
                    break
                case "=":
                    this.display_has_number_now = false
                    console.log("<<<<<<<<<<<<<<<<<<<<<<<<<")
                    console.log("display_has_number_now: "+this.display_has_number_now)
                    console.log("<<<<<<<<<<<<<<<<<<<<<<<<<")
                    this.equation = this.display_value
                    let result = this.calculateResult(this.display_value)
                    this.result = result
                    this.prev_display_value = this.equation + " = "+this.result
                    this.display_value = result
                    this.updateGuestHistory()
                    /* Save the result in database */
                    this.postHistory()
                    break
                default:
                    if(val >= 0 && val <=9) {
                        if(!this.display_has_number_now) {
                            val = " "+val
                            this.display_has_number_now = true
                            console.log("<> no numbers found infront")
                        } else {
                            val = ""+val
                            console.log("<> numbers found infront")    
                        }
                    } else {
                        val = " "+val
                        this.display_has_number_now = false
                    }
                    this.display_value += val
                    break
            }
        },
        calculateResult: function() {
            return this.calculator()
        },
        loadGuestHistory: function() {
            console.log(this.getHistory());
            this.guest_history = this.getHistory();
        },
        updateGuestHistory: function() {
            this.guest_history.push({ 
                id: this.guest_history.length + 1,
                equation: this.equation,
                result: this.result
            });
        },
        hasSpaceBefore: function() {
            var iterator = this.display_value.length - 2
            var current_val = this.display_value[iterator]
            if(current_val==" ") {
                return true
            }
            return false
        },
        fixDisplayedEquation: function(max_length=24) {
            var len = this.display_value.length
            var portioned_display_value = this.display_value
            if(len > max_length) {
                portioned_display_value = this.display_value.substr(len - max_length, len)
            }
            return portioned_display_value
        },
        calculate: function(args, operator) {
            var val = 0;
            switch(operator) {
                case "+":
                    val = args[0] + args[1];
                    break;
                case "-":
                    val = args[0] - args[1];
                    break;
                case "x":
                    val = args[0] * args[1];
                    break;
                case "/":
                    if(args[1] == 0) {
                        val = "Infinity";
                    } else {
                        val = args[0] / args[1];
                    }
                    break;
                case "%":
                    val = args[0] % args[1];
                    break;
                default:
                    console.log(args)
                    console.log(operator)
                    val = NULL;
                    break;
            }
            return val;
        },
        numberOperatorSegmentation: function (str) {
            var has_priority = false;

            var str_arr = str.split(" ")
            // console.log("str_arr : "+str_arr.toString())
            if(str_arr[0]=="" || str_arr[0]==" ") {
                console.log("!!! error found")
                str_arr = str_arr.slice(1, str_arr.length)
                // console.log("!!! modified str_arr : "+str_arr.toString())
            }
            // console.log("!!! modified str_arr : "+str_arr.toString())
            str_arr = this.checkForNan(str_arr)

            //console.log(str_arr)

            var defined_operators_arr = ["+","-","/","x","%","(",")"];
            var operators_arr = [];
            var numbers_arr = [];

            var priority_arr = [];

            var parenthesis_start_arr = [];
            var parenthesis_end_arr = [];

            var parenthesis_start_num_arr = [];
            var parenthesis_end_num_arr = [];

            var first_val_neg = false;
            var data_processing = false;

            //foreach(str_arr as seg) {
            var len = str_arr.length
            for(var k = 0; k < len; k++) {
                var seg = str_arr[k];
                // console.log(k+") working with segment: "+seg)
                if(defined_operators_arr.includes(seg)) {
                    if(data_processing == false) {
                        if(seg == "-") {
                            first_val_neg = true;
                        }
                    }
                    operators_arr.push(seg);
                    if(["/", "x"].includes(seg)) {
                        has_priority = true;
                        priority_arr.push({
                            'num_index': numbers_arr.length-1,
                            'optr_index': operators_arr.length-1
                        });
                    } else if(["(", ")"].includes(seg)) {
                        if(seg=="(") {
                            parenthesis_start_arr.push(operators_arr.length-1)
                            parenthesis_start_num_arr.push(numbers_arr.length)
                            // parenthesis_start_num_arr.push(numbers_arr.length-1)

                            console.log("() start ( ")

                            console.log("() optr index: "+(operators_arr.length-1))

                            //console.log("() num index: n"+(numbers_arr.length - 1))
                            console.log("() num index: n"+(numbers_arr.length))

                            // console.log("() num value: "+numbers_arr[numbers_arr.length - 1])
                            console.log("() num value: "+numbers_arr[numbers_arr.length])

                            console.log("() end ( ")
                            console.log("() *************")
                        } else if(seg==")") {
                            parenthesis_end_arr.push(operators_arr.length-1)
                            parenthesis_end_num_arr.push( numbers_arr.length - 1)

                            console.log("() start ) ")

                            console.log("() optr index: "+(operators_arr.length-1))

                            console.log("() num index: n"+(numbers_arr.length - 1))

                            console.log("() num value: "+numbers_arr[numbers_arr.length - 1])

                            console.log("() numbers_arr: "+numbers_arr.toString())
                            console.log("() operators_arr: "+operators_arr.toString())

                            console.log("() end ) ")
                        }
                    }
                } else {
                    if(first_val_neg==true) {
                        operators_arr.shift();
                        first_val_neg = false;
                        seg = -1 * seg;
                    }
                    numbers_arr.push(parseFloat(seg));
                }
                data_processing = true;
            }
            
            //operators_arr = this.checkForNan(operators_arr)
            numbers_arr = this.checkForNan(numbers_arr)

            return {
                'operators': operators_arr,
                'numbers': numbers_arr,
                'priority_arr': priority_arr,
                'parenthesis_arr': {
                    'parenthesis_start_arr': parenthesis_start_arr,
                    'parenthesis_end_arr':parenthesis_end_arr,
                    'parenthesis_start_num_arr':parenthesis_start_num_arr,
                    'parenthesis_end_num_arr': parenthesis_end_num_arr
                }
            };
        },
        checkForNan: function(array) {
            if(isNaN(array[0])) {
                array = array.slice(1, array.len)
            }
            return array
        },
        solvePriority: function(numbers, operators) {
            console.log("inside solvePriority")
            console.log(numbers)
            console.log(operators)
            var optr_arr_size = operators.length;
            var iterator = 0;
            while(iterator < optr_arr_size) {
                var index = iterator;
                var optr = operators[index];
                if(["/","x","%"].includes(optr)) {
                    console.log(optr)
                    var val = this.calculateValue([numbers[index], numbers[index+1]], [optr]);
                    numbers[index] = val;
                    numbers = this.unset(numbers, index+1);
                    operators = this.unset(operators, index);
                    numbers = this.fixArrayIndex(numbers);
                    operators = this.fixArrayIndex(operators);
                    optr_arr_size--;
                } else {
                    iterator++;
                }
            }
            return {
                'operators': operators,
                'numbers'  : numbers
            };
        },
        unset: function(array, index) {
            console.log("<<>> inside unset")
            console.log("<<>> array: "+array.toString())
            console.log("<<>> index: "+index)
            var len = array.length
            if(len == 1) {
                console.log("<<>> the lenght of array is 1")
                return [];
            }
            if(index < 0 || index > len) {
                console.log("<<!>> <<<<<<<<<<<<<<<>>>")
                console.log("<<!>> From unset()")
                console.log("<<!>> Error for index "+index)
                console.log("<<!>> Error for array length: "+len)
                console.log("<<!>> array: "+array.toString())
                console.log("<<!>> out of unset()")
                console.log("<<!>> <<<<<<<<<<<<<<<>>>")
                console.log(" ")
                return array
            }
            var new_arr = []
            if(index == 0) {
                console.log("<<>> the index of array is 0")
                new_arr = array.slice(1, len)
            } else if(index == len-1) {
                console.log("<<>> the index of array is last: "+index)
                new_arr = array.slice(0, len-1)
            } else {
                var arr_2 = array.slice(index+1, len)
                var arr_1 = array.slice(0, index)
                
                console.log("<<>> arr_1: "+arr_1.toString())
                console.log("<<>> arr_2: "+arr_2.toString())

                new_arr = arr_1.concat(arr_2)
            }
            console.log("<<>> after unset")
            console.log("<<>> new_arr: "+new_arr.toString())
            console.log("<<>> left unset")
            console.log("<<>> ****************")
            console.log("<<>> ")
            return new_arr
        },
        fixArrayIndex: function(array = []) {
            var new_array = []
            var len = array.length
            for(var iterator = 0; iterator<len; iterator++) {
                //let el = array[iterator]
                let el = array.shift()
                new_array[iterator] = el
            }
            // foreach($array as $index => $el) {
            //     new_array[iterator] = $el;
            //     iterator++;
            // }
            // console.log("new array from fixArrayIndex")
            // console.log(new_array)
            return new_array
        },
        calculateValue: function(numbers_arr, operators_arr) {
            if(numbers_arr.length==1 && operators_arr.length==0) {
                var val = numbers_arr[0];
            }
            while(numbers_arr.length != 1) {
                var num1 = numbers_arr.shift();
                var num2 = numbers_arr.shift();
                var optr = operators_arr.shift();
                val = this.calculate([num1, num2], optr);
                if(val !== null) {
                    if(val === "Infinity") {
                        return val;
                    } else {
                        numbers_arr.unshift(val);
                    }
                } else {
                    break;
                }
            }
            return val;
        },
        calculator: function() {
            //$str_arr = explode(" ", $str);
            console.log("<> string length: "+ this.display_value.length)
            var data = this.numberOperatorSegmentation(this.display_value)

            var operators_arr = data['operators']
            var numbers_arr = data['numbers']

            console.log("calculator - start")
            console.log(operators_arr)
            console.log(numbers_arr)
            console.log("calculator - end")


            if(data['parenthesis_arr'].parenthesis_start_arr.length > 0) {
                console.log("<<>> will check parenthesis")
                var pare_start_arr = data['parenthesis_arr'].parenthesis_start_arr
                var pare_end_arr = data['parenthesis_arr'].parenthesis_end_arr

                var pare_start_num_arr = data['parenthesis_arr'].parenthesis_start_num_arr
                var pare_end_num_arr = data['parenthesis_arr'].parenthesis_end_num_arr


                var pare_start_arr__size = pare_start_arr.length
                var pare_end_arr__size = pare_end_arr.length


                for(var i = pare_start_arr__size - 1; i >= 0 ; i--) {

                    var optr_slice_length = pare_end_arr[i] - pare_start_arr[i] - 1

                    // var operators_arr_new = array_slice(operators_arr, $pare_start_arr[i] + 1, optr_slice_length)
                    var operators_arr_new = operators_arr.slice(pare_start_arr[i] + 1, pare_end_arr[i])

                    console.log("<<!>> operators_arr_new: "+operators_arr_new.toString())

                    console.log("for parenthesis - start ************************")
                    console.log("operators_arr_new")
                    console.log("pare_start_arr[i]: "+pare_start_arr[i])
                    console.log("pare_end_arr[i]: "+pare_end_arr[i])
                    console.log("optr_slice_length: "+optr_slice_length)
                    console.log(operators_arr_new)
                    console.log("for parenthesis - end")

                    var num_slice_length = optr_slice_length + 1

                    // var numbers_arr_new = array_slice($numbers_arr, $pare_start_num_arr[$i], num_slice_length)
                    var numbers_arr_new = numbers_arr.slice(pare_start_num_arr[i], pare_end_num_arr[i] + 1)

                    console.log("<<!>> numbers_arr_new: "+numbers_arr_new.toString())

                    console.log("for parenthesis - start **************************")
                    console.log("numbers_arr_new")
                    console.log(numbers_arr_new)
                    console.log("pare_start_num_arr[i] : "+pare_start_num_arr[i])
                    console.log("pare_end_num_arr[i] : "+pare_end_num_arr[i])
                    console.log("for parenthesis - end")

                    var data_new = this.solvePriority(numbers_arr_new, operators_arr_new)
                    numbers_arr_new = data_new['numbers']
                    operators_arr_new = data_new['operators']

                    console.log("<<!>> 485 operators_arr_new: "+operators_arr_new.toString())
                    console.log("<<!>> numbers_arr_new: "+numbers_arr_new.toString())

                    
                    if(numbers_arr_new.length==1 && operators_arr_new.length==0) {
                        val = numbers_arr_new[0]
                    } else {
                        val = this.calculateValue(numbers_arr_new, operators_arr_new)
                    }


                    console.log("<<!>> 496 numbers_arr: "+numbers_arr.toString())

                    numbers_arr[pare_start_num_arr[i]] = val

                    console.log("<<!>> 500 numbers_arr: "+numbers_arr.toString())

                    console.log("<<!>> pare_start_num_arr[i]: "+ pare_start_num_arr[i])

                    console.log("<<!>> pare end index: "+(pare_start_num_arr[i]+num_slice_length))

                    console.log("<<!>> val: "+ val)
                    
                    console.log("<<!>> numbers_arr_new: "+numbers_arr_new.toString())
                    
                    console.log("<<!>> ++++++++++++++++++")

                    // for(var j = pare_start_num_arr[i] + 1; j < pare_start_num_arr[i]+num_slice_length; j++) {
  
                    //     numbers_arr = this.unset(numbers_arr, j)

                    //     console.log("<<!>> working with index j :"+j)

                    //     console.log("<<>> checking parenthesis, unseting numbers_arr")
                    //     console.log("<<>> j :"+j)
                    //     console.log("<<!>> numbers_arr: "+numbers_arr.toString())
                    //     console.log("<<!>> ----------------")
                    //     console.log("<<>> ")
                    // }

                    
                    console.log("<<*>>")
                    console.log("<<*>> for the index i: "+i)
                    console.log("<<*>> pare start index: "+pare_start_num_arr[i])
                    console.log("<<*>> pare end index: "+pare_end_num_arr[i])
                    console.log("<<*>>")

                    // for(var j = pare_start_num_arr[i]+num_slice_length; j > pare_start_num_arr[i]; j--) {
                    for(var j = pare_end_num_arr[i]; j > pare_start_num_arr[i]; j--) {    
  
                        numbers_arr = this.unset(numbers_arr, j)

                        console.log("<<!>> working with index j :"+j)

                        console.log("<<>> checking parenthesis, unseting numbers_arr")
                        console.log("<<>> j :"+j)
                        console.log("<<!>> numbers_arr: "+numbers_arr.toString())
                        console.log("<<!>> ----------------")
                        console.log("<<>> ")
                    }

                    console.log("<<!>> 514 numbers_arr: "+numbers_arr.toString())

                    // for(var j = pare_start_arr[i]; j <= pare_start_arr[i]+optr_slice_length+1; j++) {
                    //     operators_arr = this.unset(operators_arr, j)

                    //     console.log("<<>> checking parenthesis, unseting operators_arr")
                    //     console.log("<<>> j :"+j)
                    //     console.log("<<>> operators_arr: "+operators_arr.toString())
                    //     console.log("<<>> ----------------")
                    //     console.log("<<>> ")
                    // }

                    // for(var j = pare_start_arr[i]+optr_slice_length; j > pare_start_arr[i]; j--) {
                    for(var j = pare_end_arr[i]; j >= pare_start_arr[i]; j--) {
                        operators_arr = this.unset(operators_arr, j)

                        console.log("<<>> checking parenthesis, unseting operators_arr")
                        console.log("<<>> j :"+j)
                        console.log("<<>> operators_arr: "+operators_arr.toString())
                        console.log("<<>> ----------------")
                        console.log("<<>> ")
                    }

                    console.log("<<!>> 525 operators_arr: "+operators_arr.toString())
                    console.log("<<!>> ----------------")
                }
            }

            console.log("<<!>> operators_arr: "+operators_arr.toString())
            console.log("<<!>> numbers_arr: "+numbers_arr.toString())
            console.log(operators_arr)
            console.log(numbers_arr)

            /**
             * Fix the array index
             */
            numbers_arr = this.fixArrayIndex(numbers_arr)
            operators_arr = this.fixArrayIndex(operators_arr)

            console.log("after fixArrayIndex")
            console.log(operators_arr)
            console.log(numbers_arr)

            data_new = this.solvePriority(numbers_arr, operators_arr)
            numbers_arr = data_new['numbers']
            operators_arr = data_new['operators']
            console.log("after solvePriority")
            console.log(operators_arr)
            console.log(numbers_arr)

            var val;
            if(numbers_arr.length==1 && operators_arr.length==0) {
                //val = numbers_arr_new[0];
                val = numbers_arr[0]
            } else {
                val = this.calculateValue(numbers_arr, operators_arr)
            }
            return val

            //return 0
        },
        getGuest: function() {
            let axiosConfig = {
              headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
              }
            }
            axios.post(this.site_url+'/api/guest-history/get-guest', {
              'ip': this.ip,
            }, axiosConfig)
            .then((response) => {
                this.guest_id = response.data.guest_id
                return response.data
            })
            .catch(error => {
              console.log(error)
            })
        },
        getHistory: function() {
            let axiosConfig = {
              headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
              }
            }
            axios.post(this.site_url+'/api/guest-history/get-history', {
              'guest_id': this.guest_id,
            }, axiosConfig)
            .then((response) => {
                this.guest_history = response.data.guest_history
                return response.data
            })
            .catch(error => {
              console.log(error)
            })
        },
        postHistory: function() {
            let axiosConfig = {
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            }
            axios.post(this.site_url+'/api/guest-history/post-history', {
                'guest_id': this.guest_id,
                'equation': this.equation,
                'result': this.result
            }, axiosConfig)
            .then((response) => {
                console.log(response.data)
                return response.data
            })
            .catch(error => {
              console.log(error)
            })
        },
        togglePipeInDisplay: function() {
            var self = this
            setInterval(function() {
                self.display_pipe = !self.display_pipe
            }, 500)
        }
    },
    created() {
        this.getGuest()
        this.togglePipeInDisplay()
    },
    mounted() {
        setTimeout(this.getHistory, 100)
    }
}
</script>

<style scoped>
div.calculator {
    margin-top: 60px;
}
div.display {
    text-align: right;
}
div.display p, div.history, div.keys {
    font-family: 'Orbitron', sans-serif;
}
div.display p:first-child {
    font-size: 12px;
}
div.display p:last-child {
    position: relative;
    background: #ADD8E6;
    font-size: 28px;
    line-height: 36px;
    color: #fff;
    padding: 4px 15px 0px 15px;
    height: 36px;
    overflow: hidden;
}
div.display span {
    position: absolute;
    right: 5px;
}
div.keys {
    margin-top: 30px;
    text-align: center;
}
div.keys button {
    width: 23%;
    margin-bottom: 5px;
    background: #181a1b;
    border-radius: 5px;
}
div.keys button:hover {
    cursor: pointer;
}
div.history ul {
    list-style-type: none;
    max-height: 315px;
    overflow-y: scroll;
    padding: 0px;
}
::-webkit-scrollbar {
  width: 5px;
}
div.history ul li {
    padding: 10px;
    border-bottom: 1px solid #ADD8E6;
}
</style>