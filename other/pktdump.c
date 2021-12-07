/******************************************************************************
 This simple example shows how to capture packets using the the pcap library
   Copyright (C) 2000 Politecnico di Torino
*******************************************************************************/


#include <stdlib.h>
#include <stdio.h>

#include <pcap.h>

#define MAX_PRINT 80
#define MAX_LINE 16


void usage();

void main(int argc, char **argv) {
pcap_t *fp;
bpf_u_int32 SubNet,NetMask;
char error[PCAP_ERRBUF_SIZE];
struct bpf_program fcode;

	if (argc < 3)
	{
		printf("\n\t pktdump [-n adapter] | [-f file_name]\n\n");
		return;
	}

	switch (argv[1] [1])
	{
	
	case 'n':
		{
			if ( (fp= pcap_open_live(argv[2], 100, 1, 20, error) ) == NULL)
			{
				fprintf(stderr,"\nError opening adapter\n");
				return;
			}


			if(pcap_lookupnet(argv[2], &SubNet, &NetMask, error)<0){
				printf("Lookupnet failed!\n");
				pcap_close(fp);
				return ;
			}

			if(pcap_compile(fp, &fcode, "host src 1.1.1.1", 1, NetMask)<0){
				printf("Error generating the first filter. This one is wrong.\n");
			}

			if(pcap_compile(fp, &fcode, "src host 1.1.1.1", 1, NetMask)<0){
				printf("Error generating second the filter. But this filter is correct!\n");
				pcap_close(fp);
				return;
			}

			printf("All right.\n");

			pcap_close(fp);


		};
		break;
	}
		
	
}
