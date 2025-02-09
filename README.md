# alienvault-ossim

This repository contains DEB packages built and deployed via GitHub Actions. The packages are hosted on GitHub Pages along with a generated APT repository index (`Packages.gz`), allowing users to install your packages using APT. In addition, a Docker image is built and published to Docker Hub, so you can run OSSIM as a container.


## Using APT Repository

After the workflow runs successfully, follow these steps to install packages from the repository:

1. **Add the APT Repository**

   Open your APT sources list file (typically `/etc/apt/sources.list`) with a text editor using root privileges. For example:

   ```bash
   sudo nano /etc/apt/sources.list
   ```

   Then add the following line at the end of the file:

   ```plaintext
   deb [trusted=yes] https://jpalanco.github.io/alienvault-ossim/ ./
   ```

   > **Note:** The `[trusted=yes]` option is required because GitHub Pages cannot sign the repository. Alternatively, you can sign your packages with a GPG key, but that adds extra complexity.

2. **Update the Package List**

   After saving your changes, update your package list by running:

   ```bash
   sudo apt-get update
   ```

3. **Install Your Package**

   Now you can install your package (replace `<package-name>` with the actual package name):

   ```bash
   sudo apt-get install <package-name>
   ```

   APT will look up the package in your GitHub Pages repository and install it.

## Using Docker

You can also run OSSIM using Docker. The Docker image is available at Docker Hub under the tag **jpalanco/alienvault-ossim:latest**.

### Running the Docker Container Directly

To run the OSSIM Docker container directly, execute the following command:

```bash
docker run -d --name ossim \
  -p 80:80 -p 443:443 -p 8080:8080 \
  -e DB_HOST=<database-host> \
  -e DB_USER=<database-user> \
  -e DB_PASS=<database-password> \
  -e DB_NAME=<database-name> \
  jpalanco/alienvault-ossim:latest
```

Replace the environment variables with your specific configuration (for example, the hostname or IP of your database and the necessary credentials).

### Running OSSIM with Docker Compose

A sample `docker-compose.yml` file is provided below. This file defines a multi-container setup, including OSSIM and a MySQL database service.

```yaml
version: '3.8'

services:
  ossim:
    image: jpalanco/alienvault-ossim:latest
    container_name: ossim
    ports:
      - "80:80"
      - "443:443"
      - "8080:8080"
    environment:
      - DB_HOST=db
      - DB_USER=ossim
      - DB_PASS=secret
      - DB_NAME=ossim
    volumes:
      - ossim_data:/var/lib/ossim
      - ossim_logs:/var/log/ossim
    depends_on:
      - db

  db:
    image: mysql:5.7
    container_name: ossim-db
    environment:
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_DATABASE: ossim
      MYSQL_USER: ossim
      MYSQL_PASSWORD: secret
    volumes:
      - db_data:/var/lib/mysql

volumes:
  ossim_data:
  ossim_logs:
  db_data:
```

**To run OSSIM with Docker Compose:**

1. Place the above `docker-compose.yml` file at the root of your project.
2. Run the following command:

   ```bash
   docker-compose up -d
   ```

This command starts both the OSSIM container and the MySQL database container. Adjust the environment variables and volume mappings as needed for your deployment.

### Additional Docker Documentation

For more details on how to use Docker and Docker Compose, please refer to the official [Docker Documentation](https://docs.docker.com/).

## Troubleshooting

- **Artifact Not Found:**  
  If you encounter an error like "Artifact not found" during the build process, ensure that:
  - The workflow is completing successfully.
  - The artifact upload step uses a compatible version of the `actions/upload-artifact` action.
  - The artifact has not expired.

- **Dependency Issues:**  
  If you encounter dependency errors when installing a package, ensure that all required dependencies are available on your system or have been included in your package.

## Additional Information

- For more details on how APT repositories work, see the [Debian APT documentation](https://wiki.debian.org/Apt).
- If you decide to sign your packages for added security, check out [Debian package signing](https://www.debian.org/doc/manuals/debian-reference/ch05.en.html#_package_signing).

## Contributing

Contributions are welcome! If you have any suggestions, improvements, or bug fixes, please open an issue or submit a pull request.

## License

This project is licensed under the GNU General Public License version 2 (GPLv2). See the [LICENSE](LICENSE) file for details or visit [GNU GPL v2](http://www.gnu.org/licenses/gpl-2.0.html) for more information.
