<template>
	<div>
		<div v-if="loadingData">
			<div class="white-widget grey-bg author-area">
				<div class="auth-info row">
					<div class="timeline-wrapper">
						<div class="timeline-item">
							<div class="animated-background">
								<div class="background-masker header-top"></div>
								<div class="background-masker header-left"></div>
								<div class="background-masker header-right"></div>
								<div class="background-masker header-bottom"></div>
								<div class="background-masker subheader-left"></div>
								<div class="background-masker subheader-right"></div>
								<div class="background-masker subheader-bottom"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<a v-if="!loadingData" :href="url" target="_blank" class="link-espacio">
			<img v-if="espacio.image360 !== null" src="https://res.cloudinary.com/wimet/image/upload/tag_virtual.svg" class="img-3d" alt="wimet-imagen-3d">
			<img
				@click="addWhishlist($event, espacio.id)"
				:src="(inArray(espacio.id)) ? '/img/corazon_active.svg' : '/img/corazon_unactive.svg'"
				class="img-whishlist">
			<img :src="espacio.portada" :tile="espacio.name" class="img-responsive img-espacio">
			<div class="card-footer-search">
				<div>
					<strong>{{espacio.name}}</strong>
					<div class="star-row">
						<i class="fa fa-star" aria-hidden="true"></i>
						<i class="fa fa-star" aria-hidden="true"></i>
						<i class="fa fa-star" aria-hidden="true"></i>
						<i class="fa fa-star" aria-hidden="true"></i>
						<i class="fa fa-star" aria-hidden="true"></i>
					</div>
				</div>
				<div>
					<p>$ {{espacio.price}} {{(espacio.country == 'Argentina') ? 'ARS' : 'CLP'}} / media jornada</p>
					<span>
						<img class="icon-people" src="/img/wimet_ic_group_black_24px.svg" alt=""> {{espacio.quanty}}
					</span>
				</div>
			</div>
		</a>
	</div>
</template>
<script>
	export default {
		props: [
			'espacioId',
			'categoryId'
		],
		data() {
			return {
				espacio: {},
				url: '',
                loadingData: true
			}
		},
		mounted() {
			this.getEspacio();
		},
		methods: {
			getEspacio() {
				this.$http.get(`/api/getespacio/categoria/${this.categoryId}/espacio/${this.espacioId}`)
	            .then(res => {
	            	this.espacio = res.body[0];
                    this.url = `/categoria/${this.categoryId}/espacio/${this.espacio.staticname}`;
                    this.loadingData = false;
	            }, err => {
	                console.log(err);
	            });
	        },
			inArray(id) {
			    if(this.$store.getters.getUser === null) {
			        return false;
				}
			    if(this.$store.getters.getUser.espacios === undefined) {
			        return false;
				}
				for(let i = 0; i < this.$store.getters.getUser.espacios.length; i++) {
				    if(this.$store.getters.getUser.espacios[i].id === id) {
				        return true;
					}
				}
				return false;
			},
            addWhishlist(e, id) {
			    e.preventDefault();
			    if(this.$store.getters.getUser.id === undefined) {
                    this.$toastr.error('Debes iniciar sesión para poder agregar favoritos', 'Ups, no estas logueado!');
				}
                let agregar = true;
                for(let i = 0; i < this.$store.getters.getUser.espacios.length;i++) {
                    if(this.$store.getters.getUser.espacios[i].id == id) {
                        this.$store.getters.getUser.espacios.splice(i, 1);
                        agregar = false;
                    }
				}
				if(agregar) {
                    this.$store.getters.getUser.espacios.push({id: id});
				}
				this.updateUser();
			},
            updateUser() {
                this.$http.put(`api/user/${this.$store.getters.getUser.id}`, this.$store.getters.getUser)
				.then(res => {
				}, err => {
					this.$toastr.error(err, 'Ups, hubo un error!');
				});
            },
		}
	}
</script>
<style lang="sass" scoped>
	.link-espacio {
		text-decoration: none;
		color: #636b6f;
		display: flex;
		flex-direction: column;
		position: relative;
		.img-whishlist {
			position: absolute;
			right: 1em;
			top: 1em;
			box-shadow: none;
		}
		.card-footer-search {
			display: flex;
			flex-direction: column;
			width: 100%;
			height: 75px;
			.star-row{
				width: 5em !important;
			}
			div:first-child {
				width: 100%;
				display: flex;
				justify-content: space-between;
				align-items: baseline;
				padding: 0.5em 1em 0em 1em;
			}
			div:last-child {
				width: 100%;
				display: flex;
				justify-content: space-between;
				align-items: baseline;
				padding: 0em 1em 0.5em 1em;
			}
		}
	}

	.timeline-wrapper {
		padding: 1em;
	}
	.timeline-item {
		background: #fff;
		border-bottom: 1px solid #f2f2f2;
		padding: 1em 0;
		margin: 0 auto;
	}

	@keyframes placeHolderShimmer{
		0%{
			background-position: -468px 0
		}
		100%{
			background-position: 468px 0
		}
	}

	.animated-background {
		animation-duration: 1s;
		animation-fill-mode: forwards;
		animation-iteration-count: infinite;
		animation-name: placeHolderShimmer;
		animation-timing-function: linear;
		background: #f6f7f8;
		background: linear-gradient(to right, #eeeeee 8%, #dddddd 18%, #eeeeee 33%);
		background-size: 800px 104px;
		height: 40px;
		position: relative;
	}

	.background-masker {
		background: #fff;
		position: absolute;
	}

	/* Every thing below this is just positioning */

	.background-masker.header-top,
	.background-masker.header-bottom,
	.background-masker.subheader-bottom {
		top: 0;
		left: 40px;
		right: 0;
		height: 10px;
	}

	.background-masker.header-left,
	.background-masker.subheader-left,
	.background-masker.header-right,
	.background-masker.subheader-right {
		top: 10px;
		left: 40px;
		height: 8px;
		width: 10px;
	}

	.background-masker.header-bottom {
		top: 18px;
		height: 6px;
	}

	.background-masker.subheader-left,
	.background-masker.subheader-right {
		top: 24px;
		height: 6px;
	}


	.background-masker.header-right,
	.background-masker.subheader-right {
		width: auto;
		left: 300px;
		right: 0;
	}

	.background-masker.subheader-right {
		left: 230px;
	}

	.background-masker.subheader-bottom {
		top: 30px;
		height: 10px;
	}

	.background-masker.content-top,
	.background-masker.content-second-line,
	.background-masker.content-third-line,
	.background-masker.content-second-end,
	.background-masker.content-third-end,
	.background-masker.content-first-end {
		top: 40px;
		left: 0;
		right: 0;
		height: 6px;
	}

	.background-masker.content-top {
		height:20px;
	}

	.background-masker.content-first-end,
	.background-masker.content-second-end,
	.background-masker.content-third-end{
		width: auto;
		left: 380px;
		right: 0;
		top: 60px;
		height: 8px;
	}

	.background-masker.content-second-line  {
		top: 68px;
	}

	.background-masker.content-second-end {
		left: 420px;
		top: 74px;
	}

	.background-masker.content-third-line {
		top: 82px;
	}

	.background-masker.content-third-end {
		left: 300px;
		top: 88px;
	}
	.img-3d {
		width: 90px;
		position: absolute;
		top: 1em;
		left: -1em;
		box-shadow: none !important;
	}
</style>