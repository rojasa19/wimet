<template>
	<header class="login-header" :class="{'header-transparent': (transparent == 'true'), 'header-fixed': (fixed == 'true'), 'login-header--shadow': (shadow == 'true')}">
		<nav class="login-header__navbar">
			<a href="/">
				<img v-if="transparent !== 'true'" src="https://res.cloudinary.com/wimet/image/upload/wimet-logo_frbya5.svg" alt="Wimet" width="158" class="img-responsive">
				<img v-if="transparent === 'true'" src="https://res.cloudinary.com/wimet/image/upload/wimet-logo-redwhite_scy9a1.svg" alt="Wimet" width="158" class="img-responsive">
			</a>
			<div class="login-header__navbar__actions">
				<ul class="login-header__navbar__actions__menue">
					<template v-if="authenticated">
						<li>
							<a href="#" @click="reloadPage(`/dashboard#/mensajes?type=organizador`, $event)" >Mensajes</a>
						</li>
						<li>
							<img class="header-avatar" :src="$store.getters.getUser.imagesource" :alt="$store.getters.getUser.firstname">
							<a href="#" id="menu-user" :class="userloged">{{ $store.getters.getUser.firstname }}</a>
							<ul class="menue-list">
								<li class="menue-list__item" @click="reloadPage(`/dashboard#/`, $event)">
									<a href="#" style="color: #333;">Escritorio</a>
								</li>
								<li class="menue-list__item" @click="reloadPage(`/dashboard#/mensajes?type=organizador`, $event)">
									<a href="#" style="color: #333;">Mensajes</a>
								</li>
								<li class="menue-list__item" @click="reloadPage(`/dashboard#/favoritos`, $event)">
									<a href="#" style="color: #333;">Favoritos</a>
								</li>
								<li class="menue-list__item" @click="reloadPage(`/dashboard#/perfil`, $event)">
									<a href="#" style="color: #333;">Mi perfil</a>
								</li>
								<li v-if="$store.getters.getUser.espacios.length == 0 || $store.getters.getUser.espacios == undefined" class="menue-list__item active" @click="publicaUrl($event)">
									<a href="#" style="color: #fc5289;">PUBLICAR TU ESPACIO</a>
								</li>
								<li v-if="$store.getters.getUser.espacios.length > 0" class="menue-list__item" @click="reloadPage(`/dashboard#/espacios`, $event)">
									<a href="#" style="color: #333;">Mis espacios</a>
								</li>
								<li class="menue-list__last-item" @click="logout($event)">
									<a href="#" style="color: #333;">Salir</a>
								</li>
							</ul>
						</li>
					</template>
					<template v-if="!authenticated">
						<li>
							<a href="#" @click="scrollToDiv($event)">Como funciona</a>
						</li>
						<li>
							<a href="#" @click="openModalRegistro()">Registrarme</a>
						</li>
						<li>
							<a href="#" @click="openModalLogin()">Ingresar</a>
						</li>
						<li>
							<button @click="publicaUrl($event)" class="btn-publica-login">
								PUBLICA TU ESPACIO
							</button>
						</li>
					</template>
				</ul>
				<template v-if="$root.showModalLogin">
					<div class="login-modal" @click="closeModalEvent($event)">
						<div class="login-modal__content">
							<h3>¡Te estábamos esperando!</h3>
							<div v-if="msgError != ''" class="alert alert-danger" role="alert">{{msgError}}</div>
							<span class="close-modal" @click="closeModals()">×</span>
							<div class="container-social">
								<login-facebook urlredirect="0"></login-facebook>
								<login-google></login-google>
							</div>
							<div class="login-modal-title">
								<span class="text-center">Inicia sesión con tu email</span>
							</div>
							<div class="container-login">
								<input type="email" class="container-login__email" placeholder="Email" v-model="email" />
								<input type="password" class="container-login__email" placeholder="Contraseña" v-model="password" @keyup.enter="login()"/>
								<button v-if="showBtnLogin" class="container-login__login" @click="login()">Iniciar sesión</button>
								<button v-if="!showBtnLogin" class="container-login__login" @click="login()">
									<img src="https://res.cloudinary.com/wimet/image/upload/v1504053299/loading-white.svg" alt="Cargando ..." height="50px" />
								</button>
							</div>
							<div class="container-footer">
								<span class="container-footer__pregunta">¿No tienes cuenta?</span>
								<span class="container-footer__registro" @click="openModalRegistro()">Registrate</span>
							</div>
						</div>
					</div>
				</template>
				<template v-if="$root.showModalRegistro">
					<div class="login-modal" @click="closeModalEvent($event)">
						<div class="login-modal__content">
							<h3>¡Crea tu cuenta y comienza a explorar!</h3>
							<div v-if="msgError != ''" class="alert alert-danger" role="alert">{{msgError}}</div>
							<span class="close-modal" @click="closeModals()">×</span>
							<div class="container-social">
								<login-facebook urlredirect="0"></login-facebook>
								<login-google></login-google>
							</div>
							<div class="login-modal-title">
								<span class="text-center">Regístrate con tu email</span>
							</div>
							<div class="container-login">
								<input type="text" class="container-login__email" placeholder="Nombre" v-model="firstname" />
								<input type="text" class="container-login__email" placeholder="Apellido" v-model="lastname" />
								<input type="email" class="container-login__email" placeholder="Email" v-model="email" />
								<input type="password" class="container-login__email" placeholder="Contraseña" v-model="password" />
								<button v-if="showBtnLogin" class="container-login__login" @click="registrar()">Regístrate</button>
								<button v-if="!showBtnLogin" class="container-login__login" @click="login()">
									<img src="https://res.cloudinary.com/wimet/image/upload/v1504053299/loading-white.svg" alt="Cargando ..." height="50px" />
								</button>
								<div class="container-terminos">
									<input type="checkbox" id="terminos" v-model="terminos" style="display:none">
									<label for="terminos" class="container-terminos__texto">Acepto los términos y condiciones.</label>
								</div>
							</div>
							<div class="container-footer">
								<span class="container-footer__pregunta">¿Ya tienes cuenta?</span>
								<span class="container-footer__registro" @click="openModalLogin()">Inicia sesión</span>
							</div>
						</div>
					</div>
				</template>
			</div>
		</nav>
	</header>
</template>
<script>
	import loginFacebook from './loginFacebook.vue';
	import loginGoogle from './loginGoogle.vue';
	import {registerLogin} from '../mixins/registerLogin';
	export default {
        mixins: [registerLogin],
		props: ['fixed', 'shadow', 'transparent'],
		components: {
			'login-facebook': loginFacebook,
			'login-google': loginGoogle
		},
		data() {
			return {
				showModalLogin: false,
				showModalRegistro: false
			}
		},
		mounted() {
            this.getUserAuthenticated();
        },
		methods: {
            scrollToDiv(e) {
                e.preventDefault();
                $('html,body').animate({ scrollTop: $("#como-funciona").offset().top}, 'slow');
			},
			openModalLogin() {
				this.$root.showModalRegistro = false;
				this.$root.showModalLogin = true;
			},
			openModalRegistro() {
				this.$root.showModalLogin = false;
				this.$root.showModalRegistro = true;
			},
			closeModals() {
				this.$root.showModalLogin = false;
				this.$root.showModalRegistro = false;
			},
			closeModalEvent(event) {
				var specifiedElement = document.querySelector(".login-modal__content");
				var isClickInside = specifiedElement.contains(event.target);
				
				if (!isClickInside) {
					this.$root.showModalLogin = false;
					this.$root.showModalRegistro = false;
				}
			},
			reloadPage(url, event) {
				event.preventDefault();
				location.href = url;
			},
			publicaUrl(e) {
				e.preventDefault();
				this.$store.commit('setEspacio', {});
				location.href = `/publica`;
			}
		}
	}
</script>
<style lang="sass" scoped>
	a {color: #333333}
	.loader-login path, .loader-login rect{fill: #fff;}
	.login-modal {
	    position: fixed;
	    z-index: 1;
	    left: 0;
	    top: 0;
	    width: 100%;
	    height: 100%;
	    background-color: rgba(0, 0, 0, 0.4);
	    &__content {
		    width: 450px;
		    padding: 5px 20px 10px 20px;
		    margin: 2.5% auto;
		    border-radius: 2px;
		    border: 1px solid #888;
			background-color: #fefefe;
		    font-weight: 400;
		    position: relative;
		    h3 {
				opacity: 0.87;
				font-size: 18px;
				font-weight: 500;
				letter-spacing: -0.1px;
				text-align: center;
				color: #4a4a4a;
		    }
		    .login-modal-title {
		    	display: flex;
		    	justify-content: center;
		    	align-items: center;
		    	padding: 1em;
				font-size: 13px;
				font-weight: 500;
				letter-spacing: -0.2px;
				text-align: center;
				color: #888b8d;
		    }
		    .close-modal {
				color: #888b8d;
				font-size: 28px;
				font-weight: bold;
				cursor: pointer;
				position: absolute;
				top: 0;
				right: .5em;
			}
			.container-social {
			    padding-bottom: 1em;
			}
			.container-login {
			    display: flex;
			    flex-direction: column;
			    input {
			    	border-radius: 2px;
				    border: solid 1px #979797;
				    padding: 1em;
				    width: 100%;
				    height: 50px;
				    margin-bottom: 5px;
				    color: #979797;
			    }
			    &__login {
				    width: 100%;
				    height: 50px;
				    border-radius: 2px;
				    background-color: #FC5289;
				    border: none;
				    color: #fff;
				    &:hover {
				    	background-color: rgba(234, 81, 109, 0.80);
				    }
				}
				.container-terminos {
				    display: flex;
				    justify-content: center;
				    margin-top: 1em;
				    &__texto {
					    font-size: 13px;
					    font-weight: 500;
					    letter-spacing: -0.2px;
					    text-align: center;
					    color: #888b8d;
					    cursor: pointer;
					}
				}
			}
			.container-footer {
			    display: flex;
			    justify-content: center;
			    align-items: center;
			    padding: 1em 0;
			    &__pregunta {
					font-size: 14px;
					font-weight: 500;
					letter-spacing: -0.2px;
					color: #424242;
				}
				&__registro {
					color: #e2385a;
					margin-left: 1em;
					cursor: pointer;
				}
			}
	    }
	    @media only screen and (max-width: 768px) {
	    	&__content {
			    width: 100%;
			    padding: 5px 20px 10px 20px;
			    margin: 2.5% auto;
			    border-radius: 2px;
			    border: 1px solid #888;
				background-color: #fefefe;
			    font-weight: 400;
			    position: relative;
	    	}
	    }
	}
	.header-avatar {
		width: 40px;
		border-radius: 50%;
		margin-right: 1em;
	}
	.login-header {
		width: 100%;
		height: 60px;
		background-color: white;
		color: #333;
		position: relative;
		z-index: 1200;
		&--shadow {
			box-shadow: 0 1px 5px rgba(0, 0, 0, 0.1) !important;
		}
		&__navbar {
			height: 100%;
			display: flex;
			justify-content: flex-start;
			align-items: center;
			margin-right: auto;
			margin-left: auto;
			padding-left: 15px;
			padding-right: 15px;
			&__actions {
				margin-left: auto;
				&__menue {
					display: flex;
					justify-content: center;
					align-items: center;
					list-style: none;
					margin: 0px;
					li {
						float: left;
						padding: 1em;
						text-decoration: none;
						a {
							text-decoration: none;
						}
						.btn-publica-login {
							border: 2px solid #333333;
							background-color: transparent;
							color: #333333;
							padding: .5em 1em;
							border-radius: 1px;
							&:hover {
								border: 2px solid #FC5289;
								color: #FC5289;
							}
						}
					}
					.menue-list {
						display: none;
						flex-direction: column;
						background: #fff;
						padding: 1em;
						border: solid 1px #dadada;
						list-style: none;
						padding: 0;
						margin: 0;
						top: 100%;
						right: 1%;
						position: absolute;
						cursor: pointer;
						font-size: 13px;
						font-weight: 500;
						letter-spacing: -0.1px;
						color: #212121;
						z-index: 1;
						width: 14em;
						&__item {
							border-bottom: solid 1px #dadada;
							color: #FC5289 !important;
							transition: none;
							a{
								padding: 1em;
								border: none !important;
							}
							.active {
								color: #FC5289;
							}
							&:hover {
								background-color: #f8f8f8;
							}
						}
						&__last-item {
							transition: none;
							a{
								padding: 1em;
								border: none !important;
							}
							&:hover {
								background-color: #f8f8f8;
							}
						}
						&:hover {
							display: flex;
						}
					}
					&:hover .menue-list {
						display: flex;
					}
				}
			}
		}
	}
	.header-fixed {
		position: fixed;
	}
	.header-transparent {
		background-color: transparent !important;
		a {
			color: #FFFFFF;
		}
		.btn-publica-login {
			border: 2px solid #FFFFFF !important;
			color: #FFFFFF !important;
		}
	}
</style>