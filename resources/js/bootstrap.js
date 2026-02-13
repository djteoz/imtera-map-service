import axios from "axios";

axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

axios.interceptors.request.use((config) => {
	const raw = localStorage.getItem("demo_auth");

	if (!raw) {
		delete config.headers.Authorization;
		return config;
	}

	try {
		const parsed = JSON.parse(raw);
		const token = parsed?.token;

		if (token) {
			config.headers.Authorization = `Bearer ${token}`;
		} else {
			delete config.headers.Authorization;
		}
	} catch {
		delete config.headers.Authorization;
	}

	return config;
});

axios.interceptors.response.use(
	(response) => response,
	(error) => {
		const status = error?.response?.status;
		const onLoginPage = window.location.search.includes("page=login");

		if (status === 401 && !onLoginPage) {
			localStorage.removeItem("demo_auth");
			const nextUrl = `${window.location.pathname}${window.location.search}`;
			window.location.replace(`/demo.html?page=login&next=${encodeURIComponent(nextUrl)}`);
		}

		return Promise.reject(error);
	},
);

export { axios };
