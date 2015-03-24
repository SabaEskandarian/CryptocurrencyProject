#!/usr/bin/python

import sqlite3 as lite
import sys
import json
import urllib.request
import re
import time

def opToName(c):
	cprime=int(c, 16)
	if(cprime <= 75 and cprime >= 1):
		return "DATA_"+str(cprime)
	else:
		ret = {
			"00": "OP_0",
			"4c": "OP_PUSHDATA1",
			"4d": "OP_PUSHDATA2",
			"4e": "OP_PUSHDATA4",
			"4f": "OP_1NEGATE",
			"51": "OP_1",
      		        "52": "OP_2",
      		        "53": "OP_3",
       		        "54": "OP_4",
               		"55": "OP_5",
               		"56": "OP_6",
               		"57": "OP_7",
               		"58": "OP_8",
               		"59": "OP_9",
               		"5a": "OP_10",
               		"5b": "OP_11",
               		"5c": "OP_12",
               		"5d": "OP_13",
               		"5e": "OP_14",
               		"5f": "OP_15",
               		"60": "OP_16",
			"61": "OP_NOP",
			"63": "OP_IF",
			"64": "OP_NOTIF",
			"67": "OP_ELSE",
			"68": "OP_ENDIF",
			"69": "OP_VERIFY",
			"6a": "OP_RETURN",
			"6b": "OP_TOALTSTACK",
			"6c": "OP_FROMALTSTACK",
			"73": "OP_IFDUP",
			"74": "OP_DEPTH",
			"75": "OP_DROP",
			"76": "OP_DUP",
			"77": "OP_NIP",
			"78": "OP_OVER",
			"79": "OP_PICK",
			"7a": "OP_ROLL",
			"7b": "OP_ROT",
			"7c": "OP_SWAP",
			"7d": "OP_TUCK",
			"6d": "OP_2DROP",
			"6e": "OP_2DUP",
			"6f": "OP_3DUP",
			"70": "OP_2OVER",
			"71": "OP_2ROT",
			"72": "OP_2SWAP",
			"7e": "OP_CAT",
			"7f": "OP_SUBSTR",
			"80": "OP_LEFT",
			"81": "OP_RIGHT",
			"82": "OP_SIZE",
			"83": "OP_INVERT",
			"84": "OP_AND",
			"85": "OP_OR",
			"86": "OP_XOR",
			"87": "OP_EQUAL",
			"88": "OP_EQUALVERIFY",
			"8b": "OP_1ADD",
			"8c": "OP_1SUB",
			"8d": "OP_2MUL",
			"8e": "OP_2DIV",
			"8f": "OP_NEGATE",
			"90": "OP_ABS",
			"91": "OP_NOT",
			"92": "OP_0NOTEQUAL",
			"93": "OP_ADD",
			"94": "OP_SUB",
			"95": "OP_MUL",
			"96": "OP_DIV",
			"97": "OP_MOD",
			"98": "OP_LSHIFT",
			"99": "OP_RSHIFT",
			"9a": "OP_BOOLAND",
			"9b": "OP_BOOLOR",
			"9c": "OP_NUMEQUAL",
			"9d": "OP_NUMEQUALVERIFY",
			"9e": "OP_NUMNOTEQUAL",
			"9f": "OP_LESSTHAN",
			"a0": "OP_GREATERTHAN",
			"a1": "OP_LESSTHANOREQUAL",
			"a2": "OP_GREATERTHANOREQUAL",
			"a3": "OP_MIN",
			"a4": "OP_MAX",
			"a5": "OP_WITHIN",
			"a6": "OP_RIPEMD160",
			"a7": "OP_SHA1",
			"a8": "OP_SHA256",
			"a9": "OP_HASH160",
			"aa": "OP_HASH256",
			"ab": "OP_CODESEPARATOR",
			"ac": "OP_CHECKSIG",
			"ad": "OP_CHECKSIGVERIFY",
			"ae": "OP_CHECKMULTISIG",
			"af": "OP_CHECKMULTISIGVERIFY"	
		}.get(c, "?")
		return ret

def cleanScript(script):
	s=re.findall(r'..', script, re.DOTALL)
	marked = []
	for i in range(0, len(s)):
		num = int(s[i], 16)
		if num <= 75 and num >= 1 and i not in marked:
			for j in range (1, num+1):
				marked.append(i+j)
		elif num == 76 and i not in marked:
			for j in range (1, int(s[i+1], 16)+2):
				marked.append(i+j)
		elif num == 77 and i not in marked:
			for j in range (1, int("".join([s[i+1], s[i+2]]), 16)+3):
				marked.append(i+j)
		elif num == 78 and i not in marked:
			for j in range (1, int("".join([s[i+1], s[i+2], s[i+3], s[i+4]]), 16)+5):
				marked.append(i+j)
	for i in reversed(marked):
		del s[i]
	for i in range(0, len(s)):
		s[i]=opToName(s[i])
	return " ".join(s)

def insertData(script, value, cur, con):
	cur.execute("SELECT value, count FROM Scripts WHERE script=?", [script])
	con.commit()
	row = cur.fetchone()
	val = value
	count = 1
	if row is not None:
		val+=row[0]
		count+=row[1]
		cur.execute("UPDATE Scripts SET value=?, count=? WHERE script=?", (val, count, script))
	else:
		cur.execute("INSERT INTO Scripts(script, value, count) VALUES (?, ?, ?)", (script, val, count))
	con.commit()

dbName=sys.argv[1]
minBlock=int(sys.argv[2])
maxBlock=int(sys.argv[3])
con = lite.connect(dbName)
with con:
	cur = con.cursor()
	cur.execute("CREATE TABLE IF NOT EXISTS Scripts(script text, count integer, value blob)")
	for currBlock in range (minBlock, maxBlock+1):
		url='https://blockchain.info/block-height/'+str(currBlock)+'?format=json'
		print(url)
		flag = 0
		while flag == 0:
			try:
				response = urllib.request.urlopen(url)
				file = response.read()
				flag = 1
			except urllib.error.HTTPError:
				print("HTTPError, trying a second time after sleeping")
				time.sleep(5)
		text = file.decode('utf-8')
		jData = json.loads(text)
		txs = jData['blocks'][0]['tx']
		for tx in txs:
			outs = tx['out']
			for out in outs:
				val = out['value']
				script = out['script']
			insertData(cleanScript(script), val, cur, con)

	with open('by_value.txt', 'w') as outFile:
		cur.execute("SELECT script, value, count FROM Scripts ORDER BY value DESC")
		con.commit()
		rows = cur.fetchall()
		for row in rows:
			outFile.write(row[0]+"\t"+str(row[1])+"\t"+str(row[2])+"\n");
	with open('by_count.txt', 'w') as outFile:
		cur.execute("SELECT script, count, value FROM Scripts ORDER BY count DESC")
		con.commit()
		rows = cur.fetchall()
		for row in rows:
			outFile.write(row[0]+"\t"+str(row[1])+"\t"+str(row[2])+"\n");
