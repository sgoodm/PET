from osgeo import gdal,ogr
import struct
import sys
import csv

#sys.argv[0] is file name sent to python
myType = sys.argv[1]
myData = sys.argv[2]
myId = sys.argv[3]
myLon = sys.argv[4]
myLat = sys.argv[5]
myRaster = sys.argv[6]
myOutput = sys.argv[7]
myName = sys.argv[8]

src_filename = myRaster
src_ds=gdal.Open(src_filename) 
gt=src_ds.GetGeoTransform()
rb=src_ds.GetRasterBand(1)

with open(myOutput, 'w') as f:

	f.write( myId +", "+ myLon +", "+ myLat +", "+ myName +"\n" )
	
	count = 0

	if (myType == "vector"):

		shp_filename = myData
		ds=ogr.Open(shp_filename)
		lyr=ds.GetLayer()

		for feat in lyr:
			geom = feat.GetGeometryRef()

			try:
				geoid = feat.GetField(myId)
			except:
				geoid = count
			
			count += 1

			mx,my=geom.GetX(), geom.GetY()  #coord in map units

			#Convert from map to pixel coordinates.
			#Only works for geotransforms with no rotation.
			#If raster is rotated, see http://code.google.com/p/metageta/source/browse/trunk/metageta/geometry.py#493
			px = int((mx - gt[0]) / gt[1]) #x pixel
			py = int((my - gt[3]) / gt[5]) #y pixel

			structval=rb.ReadRaster(px,py,1,1,buf_type=gdal.GDT_Float32) #Assumes 16 bit int aka 'short'
			intval = struct.unpack('f' , structval) #use the 'short' format code (2 bytes) not int (4 bytes)
			f.write(str(geoid) + ", " + str(mx) + ", " + str(my) + ", " + str(intval[0])+"\n")

			# print (intval[0], file=f) #intval is a tuple, length=1 as we only asked for 1 pixel value 

	else:

		with open (myData, 'rb') as myCSV:
			csvData = csv.DictReader(myCSV, delimiter=",")

			for row in csvData:
				mx = float(row[myLon])
				my = float(row[myLat])

				try:
					geoid = row[myId]
				except:
					geoid = count
				count += 1

				px = int((mx - gt[0]) / gt[1]) #x pixel
				py = int((my - gt[3]) / gt[5]) #y pixel

				structval=rb.ReadRaster(px, py, 1, 1, buf_type=gdal.GDT_Float32) #Assumes 16 bit int aka 'short'
				intval = struct.unpack('f' , structval) #use the 'short' format code (2 bytes) not int (4 bytes)
				f.write(str(geoid) + ", " + str(mx) + ", " + str(my) + ", " + str(intval[0])+"\n")
