import http from "@js/lib/Http";
import Message from "@js/components/message";
import LoadingBar from "@js/components/loading-bar";
import { globalAccessModules } from "./globalAccessModules";

const modalable = {
    data() {
        return {
            collection: [],
            query: {
                page: 1,
            },
        };
    },
    mounted() {
        this.show = false;
        this.fetch();
    },
    methods: {
        fetch() {
            this.loading = true;
            http.get(`/api/${this.resource}`, {
                params: this.query,
            }).then((res) => {
                this.setData(res);
            });
        },
    },
};

const chartable = {
    mounted() {
        this.fetch();
    },
    methods: {
        fetch(params) {
            http.get(`/api/chart/${this.resource}`, { params })
                .then((res) => {
                    this.setGraphData(res.data)
                });
        },
    },
};

const networkChart = {
    methods: {
        fetch(data) {
            http.post(`/api/${this.resource}`, { ...data })
                .then((res) => {
                    this.setGraphData(res.data)
                });
        },
    },
};

const indexable = {
    data() {
        return {
            collection: [],
        };
    },
    beforeRouteEnter(to, from, next) {
        if (to.meta.resource) {
            http.get(`/api/${to.meta.resource}`).then((res) => {
                next((vm) => {
                    vm.setData(res);
                });
            });
        } else {
            next();
        }
    },
    beforeRouteUpdate(to, from, next) {
        if (to.meta.resource) {
            http.get(`/api/${to.meta.resource}`).then((res) => {
                this.setData(res);
                next();
            });
        }
        else {
            next();
        }
    },
    methods: {
        setData(res) {
            if (res.data.collection) {
                this.$refs.filterable.setData(res)
            };
            this.$refs.filterable.setAllData(res.data)

            this.$bar.finish();
        },
        removeDB(resource, id) {
            const r = confirm(this.$t("are_you_sure"));
            if (r != true) {
                return;
            }

            this.$bar.start();
            this.$http
                .delete(`/api/${resource}/${id}`)
                .then((res) => {
                    if (res.data.deleted) {
                        this.$message.success(this.$t("success_delete"));
                        this.$http.get(`/api/${resource}`).then((res) => {
                            this.setData(res);
                        });
                        this.$refs.filterable.fetch();
                    }
                })
                .catch((error) => {
                    if (error.response && error.response.status === 422) {
                        this.$message.error(error.response.data.message);
                    }
                })
                .finally(() => {
                    this.$bar.finish();
                });
        },
    },
};

const showable = {
    data() {
        return {
            bu_access: false,
        };
    },
    
    mounted() {
        this.bu_access = this.$store.state.app.user_data?.bu_access;
    },
    beforeRouteEnter(to, from, next) {
        http.get(`/api/${to.meta.resource}/${to.params.id}`).then((res) => {
            next((vm) => {
                vm.setData(res);
            });
        });
    },
    beforeRouteUpdate(to, from, next) {
        http.get(`/api/${to.meta.resource}/${to.params.id}`).then((res) => {
            this.setData(res);
            next();
        });
    },
    methods: {
        setData(res) {
            // Vue 3: Direct assignment instead of $set
            this.model = res.data.model;
            this.$bar.finish();
            this.show = true;
        },
        fetch() {
            // Vue 3: Use $route instead of $router.currentRoute
            http.get(`/api/${this.$route?.meta?.resource}/${this.$route?.params?.id}`).then((res) => {
                this.setData(res);
            });
        },
        removeDB(resource, id) {
            const r = confirm(this.$t("are_you_sure"));
            if (r != true) {
                return;
            }

            this.$bar.start();
            this.$http
                .delete(`/api/${resource}/${id}`)
                .then((res) => {
                    if (res.data.deleted) {
                        this.$router.push(`/${resource}`);
                        this.$message.success(this.$t("success_delete"));
                    }
                })
                .catch((error) => {
                    if (error.response && error.response.status === 422) {
                        this.$message.error(error.response.data.message);
                    }
                })
                .finally(() => {
                    this.$bar.finish();
                });
        },
    },
};

const formable = {
    data() {
        return {
            isSaving: false,
            show: false,
            bu: {},
            form: {
                organization: {},
            },
            errors: {},
            bu_access: false,
        };
    },
    mounted() {
        if (this.$store.state.app.bu?.id) {
            this.bu = this.$store.state.app.bu;
        }else{
            this.bu = this.$store.state.app.defaultBU;
        }
        this.bu_access = this.$store.state.app.user_data?.bu_access;
        if(this.form?.bu?.id !== this.bu?.id && this.form?.id && !globalAccessModules.includes(this.$route.meta.resource)){
            this.$router.push(`/${this.redirect}/${this.form.id}`);
        }
    },
    beforeRouteEnter(to, from, next) {
        if(!to.meta.resource) {
            LoadingBar.finish();
            return next();
        }
        http.get(`/api/${getURL(to)}`, { params: to.query }).then((res) => {
            next((vm) => {
                vm.setData(res);
            });
        }).catch((error)=>{
            if (error.response && error.response.data && error.response.data.message) {
                Message.error(error.response.data.message);
            } else {
                Message.error('Something went wrong.');
            }
            LoadingBar.finish();
            next(false); 

        });
    },
    beforeRouteUpdate(to, from, next) {
        if(!to.meta.resource) {
            this.$bar.finish();
            return next();
        }
        http.get(`/api/${getURL(to)}`, { params: to.query }).then((res) => {
            this.setData(res);
            next();
        });
    },
    computed: {
        mode() {
            return this.$route.meta.mode;
        },
    },
    methods: {
        setData(res) {
            // Vue 3: Direct assignment instead of $set
            this.form = res.data.form;
            this.$bar.finish();
            this.show = true;
        },
        cancel() {
            let r = this.$route.meta.resource;
            let id = this.$route.params.id;
            let redirect = this.redirect
            let url = `/${redirect ? redirect : r}`;
            if (this.mode === "edit") {
                url = `/${redirect ? redirect : r}/${id}`;
            }

            this.$router.push(url);
        },
        save() {
            this.isSaving = true;
            this.errors = {};

            const { url, method } = this.getForm();

            this.$http[method](url, this.form)
                .then((res) => {
                    this.$router.push(`/${this.redirect}/${res.data.id}`);
                    this.$message.success(this.$t("saved_success"));
                })
                .catch(this.catch)
                .finally(() => {
                    this.isSaving = false;
                });
        },
        catch(error) {
            console.log(error);
            if (error.response && error.response.status === 422) {
                this.errors = error.response.data.errors;
                this.$message.error(error.response.data.message);
            }
        },
        getForm() {
            let r = this.$route.meta.resource;
            let id = this.$route.params.id;
            let url = `/api/${r}`;
            let method = "post";

            if (this.mode === "edit") {
                url = `/api/${r}/${id}`;
                method = "put";
            }

            return {
                url,
                method,
            };
        },
    },
};

const settings = {
    data() {
        return {
            isSaving: false,
            show: false,
            bu_access: false,
            form: {},
            errors: {},
        };
    },
    beforeRouteEnter(to, from, next) {
        if(!to.meta.resource) {
            LoadingBar.finish();
            return next();
        }
        try {  
            http.get(`/api/${to.meta.resource}`).then((res) => {
                next((vm) => {
                    vm.setData(res);
                });
            });
        
        } catch (error) {
            next((vm) => {
                vm.cancel();
            });    
        }
    },
    beforeRouteUpdate(to, from, next) {
        if(!to.meta.resource) {
            this.$bar.finish();
            return next();
        }
        http.get(`/api/${to.meta.resource}`).then((res) => {
            this.setData(res);
            next();
        });
    },
    
    mounted() {
        this.bu_access = this.$store.state.app.user_data?.bu_access;
    },
    computed: {
        mode() {
            return this.$route.meta.mode;
        },
    },
    methods: {
        setData(res) {
            // Vue 3: Direct assignment instead of $set
            this.form = res.data.form;
            this.bu_access = this.$store.state.app.user_data?.bu_access;
            if (res.data.collection && res.data.collection.data.length > 0) {
                this.$refs.filterable.setData(res)
            };
            this.$bar.finish();

            this.show = true;
        },
        cancel() {
            let r = this.$route.meta.resource;

            let url = `/${r}?cancel`;

            this.$router.push(url)
        },
        save() {
            this.isSaving = true;
            this.errors = {};

            const { url, method } = this.getForm();

            this.$http[method](url, this.form)
                .then((res) => {
                    const id = Math.random().toString(36).substring(7);
                    this.$router.push(`/${this.redirect}?id=${id}`);
                    this.$message.success(this.$t("saved_success"));
                })
                .catch(this.catch)
                .finally(() => {
                    this.isSaving = false;
                });
        },
        catch(error) {
            console.log(error);
            if (error.response && error.response.status === 422) {
                this.errors = error.response.data.errors;
                this.$message.error(error.response.data.message);
            }
        },
        getForm() {
            let r = this.$route.meta.resource;

            let url = `/api/${r}`;
            let method = "post";

            return {
                url,
                method,
            };
        },
        removeDB(resource, id) {
            const r = confirm(this.$t("are_you_sure"));
            if (r != true) {
                return;
            }

            this.$bar.start();
            this.$http
                .delete(`/api/${resource}/${id}`)
                .then((res) => {
                    if (res.data.deleted) {
                        this.$message.success(this.$t("success_delete"));
                        this.$http.get(`/api/${resource}`).then((res) => {
                            this.setData(res);
                        });
                    }
                })
                .catch((error) => {
                    if (error.response && error.response.status === 422) {
                        this.$message.error(error.response.data.message);
                    }
                })
                .finally(() => {
                    this.$bar.finish();
                });
        },
    },
};

const users = {
    data() {
        return {
            isSaving: false,
            show: false,
            form: {},
            errors: {},
        };
    },
    beforeRouteEnter(to, from, next) {
        http.get(`/${to.meta.resource}`).then((res) => {
            next((vm) => {
                vm.setData(res);
            });
        });
    },
    beforeRouteUpdate(to, from, next) {
        http.get(`/${to.meta.resource}`).then((res) => {
            this.setData(res);
            next();
        });
    },
    computed: {
        mode() {
            return this.$route.meta.mode;
        },
    },
    methods: {
        setData(res) {
            // Vue 3: Direct assignment instead of $set
            this.form = res.data.form;
            if (res.data.collection) this.$refs.filterable.setData(res);
            this.$bar.finish();
            this.show = true;
        },
        cancel() {
            let r = this.$route.meta.resource;

            let url = `/${r}?cancel`;

            // this.$router.push(url)
        },
        save() {
            this.isSaving = true;
            this.errors = {};

            const { url, method } = this.getForm();

            this.$http[method](url, this.form)
                .then((res) => {
                    const id = Math.random().toString(36).substring(7);
                    this.$router.push(`/${this.redirect}?id=${id}`);
                    this.$message.success(this.$t("saved_success"));
                })
                .catch(this.catch)
                .finally(() => {
                    this.isSaving = false;
                });
        },
        catch(error) {
            console.log(error);
            if (error.response && error.response.status === 422) {
                this.errors = error.response.data.errors;
                this.$message.error(error.response.data.message);
            }
        },
        getForm() {
            let r = this.$route.meta.resource;

            let url = `/${r}`;
            let method = "post";

            return {
                url,
                method,
            };
        },
    },
};

function getURL(to) {
    let urls = {
        create: `${to.meta.resource}/create`,
        edit: `${to.meta.resource}/${to.params.id}/edit`,
        clone: `${to.meta.resource}/${to.params.id}/edit?mode=clone`,
    };
    return urls[to.meta.mode] || urls["create"];
}

export { indexable, showable, formable, modalable, settings, chartable, networkChart, users };

