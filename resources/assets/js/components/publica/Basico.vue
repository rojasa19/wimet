<template>
    <div class="wt-m-top-5 wt-m-bot-5">
        <h1 class="publica-titulo">¡Hola {{$store.getters.getUser.firstname}}!</h1>
        <h1 class="publica-titulo">Comencemos por lo esencial…</h1>
        <div v-if="estiloespacio.length > 0 && categories.length > 0" class="row">
            <div class="col-xs-12 col-md-6">
                <div class="row wt-m-top-3">
                    <div class="col-xs-12 col-md-3">
                        <label class="label-publica">TIPO(S) DE USO PERMITIDO(S)</label>
                    </div>
                    <div class="col-xs-12 col-md-9 container-categories">
                        <div
                            v-for="(cat, key) in $store.getters.getCategories"
                            class="container-categories__box"
                            :class="{
                                'container-categories__box--active': inArray(cat.id, espacio.categorias),
                                'container-categories__box--simple': key !== 0,
                                'container-categories__box--complete': key === 0
                            }"
                            @click="selectCategory(cat)">
                            <img :src="cat.icon" :alt="cat.name">
                            <span class="wt-mayuscula">{{cat.name}}</span>
                        </div>
                        <span class="categorias">Estos espacios generalmente incluyen acceso a mesas y sillas y estar pensados para grupos de trabajo.</span>
                    </div>
                </div>
                <div class="phone">
                    <div class="row wt-m-top-3">
                        <div class="col-xs-12 col-md-3">
                            <label class="label-publica">DIRECCIÓN</label>
                        </div>
                        <div class="col-xs-12 col-md-9">
                            <vue-google-autocomplete
                                ref="address"
                                id="publica-map"
                                classname="input-publica"
                                placeholder="El Salvador 5218, Buenos Aires"
                                v-on:placechanged="getAddressData">
                            </vue-google-autocomplete>
                            <template v-if="showMapa">
                                <mapa-publica
                                    class="wt-m-top-3"
                                    name="publica-mapa"
                                    icon="location"
                                    zoom="8"
                                    gwith="100%"
                                    gheight="145px"
                                    :gespacios="[espacio]"
                                >
                                </mapa-publica>
                            </template>
                        </div>
                    </div>
                    <div class="row wt-m-top-3">
                        <div class="col-xs-12 col-md-3">
                            <label class="label-publica">TELÉLFONO</label>
                        </div>
                        <div class="col-xs-12 col-md-9">
                            <input class="input-publica" type="text" placeholder="11 5555-5555" v-model="espacio.phone">
                            <span class="phoneAlert">Tu dirección y número de teléfono nunca serán compartidos públicamente. Solo compartiremos esta información de forma privada con un organizador que haya hecho una reserva tentativo o aceptado una propuesta.</span>
                        </div>
                    </div>
                </div>
                <div class="wt-space-block wt-m-top-3">
                    <button class="btn-publica-step-default" @click="back()">
                        <img src="https://res.cloudinary.com/wimet/image/upload/v1512746740/ic_keyboard_backspace_black_24px.svg">
                        <span>ATRÁS</span>
                    </button>
                    <img v-show="!btnSend" src="https://res.cloudinary.com/wimet/image/upload/v1504053299/loading-pig_oxestq.svg" alt="Cargando ..." height="50px" />
                    <button v-show="btnSend && $store.getters.getEspacio.id === undefined" class="btn-publica-step-primary" @click="saveEspacio()">GUARDAR Y CONTINUAR</button>
                    <button v-show="btnSend && $store.getters.getEspacio.id !== undefined" class="btn-publica-step-primary" @click="updateEspacio()">GUARDAR Y CONTINUAR</button>
                </div>
            </div>
            <div class="col-md-6">
                <img src="https://res.cloudinary.com/wimet/image/upload/v1512791614/wimet_basico.svg" class="img-responsive" style="width: 80%; float: right">
            </div>
        </div>
    </div>
</template>
<script>
    import VueGoogleAutocomplete from 'vue-google-autocomplete';
    import MapaPublica from '../GoogleMaps.vue';
    export default {
        name: 'basico',
        components: {
            VueGoogleAutocomplete,
            'mapa-publica': MapaPublica
        },
        data() {
            return {
                espacio: (this.$store.getters.getEspacio.id !== undefined) ? this.$store.getters.getEspacio : {categorias: []},
                estiloespacio: [],
                categories: [],
                showMapa: (this.$store.getters.getEspacio.id !== undefined) ? true : false,
                address: [],
                btnSend: true
            }
        },
        mounted() {
            this.getEstiloespacio();
            this.getCategories();
        },
        methods: {
            getEstiloespacio() {
                this.$http.get('api/estiloespacio')
                .then(res => {
                    this.estiloespacio = res.body;
                }, err => {
                    console.log(err);
                });
            },
            getCategories() {
                this.$http.get('api/categoria')
                .then(res => {
                    this.categories = res.body;
                    this.$store.commit('setCategories', res.body);
                }, err => {
                    console.log(err);
                });
            },
            selectCategory(cat) {
                let index = this.espacio.categorias.indexOf(cat);
                if (index > -1) {
                    this.espacio.categorias.splice(index, 1);
                } else {
                    this.espacio.categorias.push(cat);
                }
            },
            inArray(item, arr) {
                for(let i = 0; i < arr.length; i ++) {
                    if(arr[i].id === item) {
                        return true;
                    }
                }
                return false;
            },
            getAddressData(addressData, placeResultData, id) {
                this.espacio.lat = addressData.latitude;
                this.espacio.long = addressData.longitude;
                this.espacio.country = addressData.country;
                this.espacio.city = addressData.administrative_area_level_1;
                this.espacio.adress = `${addressData.route} ${addressData.street_number}`;
                this.showMapa = true;
            },
            saveEspacio() {
                if(this.espacio.categorias.length == 0) {
                    this.$toastr.error("Debe ingresar al menos una categoria", "Ups...");
                    return;
                }
                if(this.espacio.adress == undefined) {
                    this.$toastr.error("Debe ingresar una dirección", "Ups...");
                    return;
                }
                if(this.espacio.phone == undefined) {
                    this.$toastr.error("Debe ingresar un teléfono", "Ups...");
                    return;
                }
                this.btnSend = false;
                this.espacio.name = `wimet-${Date.now()}`;
                this.espacio.description = '';
                this.espacio.step = (this.espacio.step < 2) ? 2 : this.espacio.step;
                this.espacio.estilos = [this.espacio.estilos];
                this.espacio.step = 1;
                this.espacio.user_id = this.$store.getters.getUser.id;
                this.$http.post('api/espacio', this.espacio)
                .then(res => {
                    this.getEspacio(res.body.id);
                }, err => {
                    this.btnSend = true;
                    console.log(err);
                });
            },
            updateEspacio() {
                console.log('entre update');
                this.btnSend = false;
                this.$http.put(`api/espacio/${this.$store.getters.getEspacio.id}`, this.espacio)
                .then(res => {
                    this.getEspacio(this.$store.getters.getEspacio.id);
                }, err => {
                    this.btnSend = true;
                    console.log(err);
                });
            },
            getEspacio(id) {
                this.$http.get(`api/espacio/${id}`)
                .then(res => {
                    this.$store.commit('setEspacio', res.body);
                    this.$router.push({ name: 'actividad', params: { name: res.body.categorias[0].name }});
                }, err => {
                    this.btnSend = true;
                    this.$toastr.error(err, "Ups... hubo un error");
                });
            },
            back() {
                this.$router.push({ name: "resumen"});
            }
        }
    }
</script>
<style lang="sass" scoped>
    .container-categories {
        display: flex;
        justify-content: center;
        align-items: center;
        &__box {
            transition: none;
            display: flex;
            min-height: 80px;
            width: 25%;
            flex-direction: column;
            justify-content: space-around;
            align-items: center;
            padding: 1em;
            cursor: pointer;
            font-size: 12px;
            color: #212121;
            &--active {
                background-color: #f8f8f8;
            }
            &--simple {
                border-right: solid 1px #dadada;
                border-top: solid 1px #dadada;
                border-bottom: solid 1px #dadada;
            }
            &--complete {
                border: solid 1px #dadada;
            }
        }
        &:hover .categorias {
            display: block;
        }
    }
    .categorias {
        display: none;
        width: 70%;
        position: absolute;
        right: -22em;
        padding: 1em;
        border-radius: 1px;
        border: 1px solid #dadada;
        box-shadow: 0 0 9px 0 rgba(0, 0, 0, 0.25);
        z-index: 1;
        background: #fff;
    }
    .phoneAlert {
        display: none;
        width: 70%;
        position: absolute;
        right: -22em;
        padding: 1em;
        border-radius: 1px;
        top: -12em;
        border: 1px solid #dadada;
        box-shadow: 0 0 9px 0 rgba(0, 0, 0, 0.25);
        z-index: 1;
        background: #fff;
    }
    .phone:hover .phoneAlert {
        display: block;
    }
    input::placeholder {
        color: #dadada;
    }
</style>
