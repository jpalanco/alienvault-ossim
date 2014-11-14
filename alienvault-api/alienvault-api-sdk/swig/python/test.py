import api

api.sim_api_initialization()
sim_api = api.sim_api_new()
api.sim_api_login(sim_api, "192.168.5.120", 40011, "admin", "alien4ever")
print api.sim_api_request(sim_api, "https://192.168.5.120:40011/av/api/1.0/config/sensors")
