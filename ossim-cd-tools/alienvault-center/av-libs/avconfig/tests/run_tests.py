# -*- coding: utf-8 -*-
#
# © Alienvault Inc. 2012
# All rights reserved
#
# This code is protected by copyright and distributed under licenses
# restricting its use, copying, distribution, and decompilation. It may
# only be reproduced or used under license from Alienvault Inc. or its
# authorised licensees.
import sys, os
sys.path.insert(0,os.path.dirname(os.path.abspath(os.path.join(__file__, os.pardir))))
import unittest2 as unittest
from utils_tests import TestUtils
from ossimsetupconfig_tests import TestAVOssimSetupConfigHandlerPublicAPI
from ossimsetupconfig_get_tests import TestAVOssimSetupConfigHandlerGets
from ossimsetupconfig_set_tests import TestAVOssimSetupConfigHandlerSets
from ossimsetupconfig_check_tests import TestAVOssimSetupConfigHandlerChecks
from configparser_tests import TestAVConfigParser
from configparsererror_tests import TestAVConfigParserErrors
import xmlrunner
from optparse import OptionParser
def suite():
    """Build the library test suit
    """
    suite = unittest.TestSuite()
    #Add all the test cases here
    suite.addTest(unittest.makeSuite(TestUtils))
    suite.addTest(unittest.makeSuite(TestAVConfigParserErrors))
    suite.addTest(unittest.makeSuite(TestAVConfigParser))
    suite.addTest(unittest.makeSuite(TestAVOssimSetupConfigHandlerPublicAPI))
    suite.addTest(unittest.makeSuite(TestAVOssimSetupConfigHandlerGets))
    suite.addTest(unittest.makeSuite(TestAVOssimSetupConfigHandlerChecks))
    suite.addTest(unittest.makeSuite(TestAVOssimSetupConfigHandlerSets))
    return suite


if __name__ == "__main__":
    parser = OptionParser()
    parser.add_option("-o","--output", dest="output_file", help="Output file name and path",metavar="FILE" )
    parser.add_option("-f","--format", dest="format",help="Output format. Allowed options are text, xml and  stdout", default="stdout")
    parser.add_option("-v","--verbosity", dest="verbosity_level", help="The verbosity level [0,1,2] ", default = 1)

    (options, args) = parser.parse_args()

    if options.format not in ["xml", "stdout", "text"]:
        parser.exit(1, "Invalid output format")
    if options.format == "xml" and options.output_file == None:
        parser.exit(1, "Invalid output file. When you choose the xml format as output you should specify a valid output file")
    if options.format == "text" and options.output_file == None:
        parser.exit(1, "Invalid output file. When you choose the text format as output you should specify a valid output file")

    outputfile = None
    if options.format == "stdout":
        runner = unittest.TextTestRunner(verbosity=1)
    elif options.format == "text":
        outputfile = open(options.output_file,"w")
        runner = unittest.TextTestRunner(stream = outputfile, verbosity=1)
    elif options.format == "xml":
        runner = xmlrunner.XMLTestRunner(output=options.output_file)
        
    runner.run(suite())
    if outputfile is not None:
        outputfile.close()
