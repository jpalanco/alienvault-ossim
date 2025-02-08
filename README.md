# alienvault-ossim

This repository contains DEB packages built and deployed via GitHub Actions. The packages are hosted on GitHub Pages along with a generated APT repository index (`Packages.gz`), allowing users to install your packages using APT.

## Build and Deployment

A GitHub Actions workflow builds the DEB packages on Debian Stretch and then deploys them to GitHub Pages. Once the workflow has run successfully, your GitHub Pages URL (for example, `https://jpalanco.github.io/alienvault-ossim/`) will contain the DEB packages and the repository index file.

## Using Your New APT Repository

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
```

---

### Explanation

- **Build and Deployment:**  
  This section explains how the DEB packages are built and deployed to GitHub Pages via a GitHub Actions workflow.

- **Using Your New APT Repository:**  
  Detailed instructions are provided on how users can add your GitHub Pages URL to their `/etc/apt/sources.list`, update their package list, and install packages.

- **Troubleshooting:**  
  Common issues and troubleshooting tips are listed.

- **Additional Information & Contributing:**  
  Extra details and contribution guidelines are provided.

- **License:**  
  The license section now clearly states that the project is licensed under GNU GPL V2.

Feel free to adjust any sections to better suit your project's specifics.
