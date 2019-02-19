import logging
import gzip
import os

def compress_file(filename):
    try:
        fo = open(filename)

    except IOError, e:
        logging.error("Error opening file: %s" % (e))
        return False

    fd = gzip.GzipFile(filename + ".gz", 'wb')
    fd.write(fo.read())
    fo.close()
    fd.close()
    os.chmod(filename + ".gz", 0644)
    try:
        os.unlink(filename)
    except OSError, e:
        logging.error("Error removing file: %s" % (e))
        return False

    logging.info("Created GZIPPED file %s.gz [%s bytes]" % (filename, str(os.path.getsize(filename + ".gz"))))
    return True

def extract_file(filename, output_file):
    """Unzip a  file to the output_file
    """
    rt = True
    try:
        ff = gzip.open(filename, 'rb')
        data = ff.read()
        ff.close()
        fd = open(output_file, 'w')
        fd.write(data)
        fd.close()
        os.chmod(output_file, 0644)
    except Exception, e:
        logging.error("Error decompressing the file %s %s " % (output_file, str(e)))
        rt = False
    return rt


def remove_file(filetodelete):
    rt = True
    try:
        os.unlink(filetodelete)
    except Exception,e:
        logging.error("Error deleting the file %s" % str(e))
        rt =False
    return rt