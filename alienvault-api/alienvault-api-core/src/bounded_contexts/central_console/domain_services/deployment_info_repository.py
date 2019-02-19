class DeploymentInfoRepository(object):

    def __init__(self, model_constructor, control_node_repo, license_repo, sensor_repo):
        self.__model_constructor = model_constructor
        self.__control_node_repository = control_node_repo
        self.__license_repository = license_repo
        self.__sensor_repository = sensor_repo

    def get_deployment_info(self):
        info = self.__model_constructor(
            self.__control_node_repository.get_control_node(),
            self.__license_repository.get_license(),
            self.__sensor_repository.get_sensors()
        )

        return info
