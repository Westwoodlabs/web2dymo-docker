#!/usr/bin/env python3

import time, sys, os, subprocess

print("Add printers to CUPS..")


while True:
    p = subprocess.Popen(['lpstat', '-o'], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    stdout, stderr = p.communicate()
    if p.returncode == 0:
        break
    else:
        print("Wait for cupsd to be ready...")
        time.sleep(1)

print("cupsd is ready")

totalPrinterCount = 0
enabledPrinterCount = 0

i = 0
while True:
    i = i + 1
    printerEnable = os.getenv('PRINTER{}_ENABLE'.format(i))
    if printerEnable == None:
        break
    totalPrinterCount += 1

    if not printerEnable == "1":
        continue
    enabledPrinterCount += 1

    printerName = os.getenv('PRINTER{}_NAME'.format(i))
    printerDevURI = os.getenv('PRINTER{}_DEVURI'.format(i))
    printerPPDFile = os.getenv('PRINTER{}_PPDFILE'.format(i))

    # Add Printer
    print("Adding printer '{name}'".format(
        name = printerName,
    ))

    subprocess.call("lpadmin -p {} -v {} -P {}".format(printerName, printerDevURI, printerPPDFile), shell=True)
    subprocess.call("cupsenable {}".format(printerName), shell=True)
    subprocess.call("cupsaccept {}".format(printerName), shell=True)

    print("Printer '{name}' added".format(
        name = printerName,
    ))

print("{}/{} enabled Printers.".format(enabledPrinterCount, totalPrinterCount))
time.sleep(3) # Sleep for 3 seconds
if enabledPrinterCount == 0:
  exit(0)
