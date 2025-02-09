# Use Debian 9 (Stretch) as the base image
FROM debian:9

# Update and install basic dependencies
RUN apt-get update && apt-get install -y \
    apt-utils \
    dpkg \
    && rm -rf /var/lib/apt/lists/*

# Copy the DEB packages from the build context (provided via the 'debs' folder)
COPY debs/ /tmp/debs/

# Install the DEB packages and fix any dependency issues
RUN dpkg -i /tmp/debs/*.deb || apt-get -f install -y && rm -rf /tmp/debs

# (Optional) Expose any necessary ports or set environment variables
# EXPOSE 80 443

# Set the default command (adjust as needed for your application)
CMD ["/bin/bash"]
