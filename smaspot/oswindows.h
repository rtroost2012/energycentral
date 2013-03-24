/************************************************************************************************
	SMAspot - Yet another tool to read power production of SMA solar inverters
	(c)2012-2013, SBF (mailto:s.b.f@skynet.be)

	Latest version found at http://code.google.com/p/sma-spot/

	License: Attribution-NonCommercial-ShareAlike 3.0 Unported (CC BY-NC-SA 3.0)
	http://creativecommons.org/licenses/by-nc-sa/3.0/

	You are free:
		to Share — to copy, distribute and transmit the work
		to Remix — to adapt the work
	Under the following conditions:
	Attribution:
		You must attribute the work in the manner specified by the author or licensor
		(but not in any way that suggests that they endorse you or your use of the work).
	Noncommercial:
		You may not use this work for commercial purposes.
	Share Alike:
		If you alter, transform, or build upon this work, you may distribute the resulting work
		only under the same or similar license to this one.

DISCLAIMER:
	A user of SMAspot software acknowledges that he or she is receiving this
	software on an "as is" basis and the user is not relying on the accuracy
	or functionality of the software for any purpose. The user further
	acknowledges that any use of this software will be at his own risk
	and the copyright owner accepts no responsibility whatsoever arising from
	the use or application of the software.

************************************************************************************************/

#ifndef OSWINDOWS_H_INCLUDED
#define OSWINDOWS_H_INCLUDED

#ifndef WIN32
#error Do Not include oswindows.h on non-windows systems
#endif

#define _CRT_SECURE_NO_WARNINGS	//Ignore C4996 Warnings (The POSIX name for this item is deprecated. Instead, use the ISO C++ conformant name)
//TODO: Fix the code to avoid these warnings
#pragma warning(disable: 4996)
#pragma warning(disable: 4127)	// Ignore 'Conditional expression is constant' warning generated by FD_SET

#define _USE_32BIT_TIME_T
#include <time.h>
#include <string.h>

#define sleep(sec) Sleep(sec*1000)

//Ignore C4996 Warnings (The POSIX name for this item is deprecated. Instead, use the ISO C++ conformant name)
#define snprintf sprintf_s

char *strptime (const char *buf, const char *format, struct tm *timeptr);

#include <direct.h>	// _mkdir
#include <io.h>		// filelength

typedef unsigned char BYTE;

#endif // OSWINDOWS_H_INCLUDED
