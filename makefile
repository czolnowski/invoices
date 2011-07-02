init:
	echo "Type 'make test' or 'make clean'";
test:
	./invoice invoices/invoice_01.inv invoices/pdf/;
	./invoice invoices/invoice_02.inv invoices/pdf/;
	./invoice invoices/invoice_03.inv invoices/pdf/;
	./invoice invoices/invoice_04.inv invoices/pdf/;
	./invoice invoices/invoice_05.inv invoices/pdf/;
	./invoice invoices/invoice_06.inv invoices/pdf/;
	./invoice invoices/invoice_07.inv invoices/pdf/;
	./invoice invoices/invoice_08.inv invoices/pdf/;
	./invoice invoices/invoice_09.inv invoices/pdf/;
	./invoice invoices/invoice_10.inv invoices/pdf/;
	
clean:
	rm invoices/pdf/*
