FROM ubuntu

# Set workdir
#WORKDIR /opt/cups

ENV DEBIAN_FRONTEND=noninteractive

# Install driver and CUPS
RUN apt-get update -y && apt-get install -y wget supervisor cups cups-client printer-driver-dymo apache2 php libapache2-mod-php php-imagick python3
RUN mkdir -p /var/log/supervisor

# Apache log to stdout
RUN ln -sf /proc/self/fd/1 /var/log/apache2/access.log && \
    ln -sf /proc/self/fd/1 /var/log/apache2/error.log

# Change ImageMagick policy
RUN sed -i 's/<policy domain="coder" rights="none" pattern="PDF" \/>/<policy domain="coder" rights="read|write" pattern="PDF" \/>/g' /etc/ImageMagick-6/policy.xml


# Download and install Dymo printer definition
WORKDIR /tmp
RUN wget http://download.dymo.com/dymo/Software/Download%20Drivers/Linux/Download/dymo-cups-drivers-1.4.0.tar.gz && \
    tar -xzf dymo-cups-drivers-1.4.0.tar.gz && \
    mkdir -p /usr/share/cups/model && \
    cp ./dymo-cups-drivers-1.4.0.5/ppd/*.ppd /usr/share/cups/model

# Copy Zebra printer driver definition
COPY ./zebraep2.ppd /usr/share/cups/model/zebraep2.ppd

# add printers
#COPY addprinters.sh addprinters.sh
#RUN chmod +x addprinters.sh
COPY ./addprinters.py /addprinters.py

# supervisord
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

CMD ["/usr/bin/supervisord"]