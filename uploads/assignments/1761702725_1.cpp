#include<iostream>
using namespace std;
class Phanso{
    private:
    	int TS,MS; 
    public:
    	Phanso();
    	Phanso(int a, int b);
    	void nhapPS();
    	Phanso operator + (Phanso b);
    	friend Phanso operator + (int x, Phanso b);
    	void inPS();
};
Phanso Phanso :: operator + (Phanso b){
    Phanso c;
    c.TS= TS*b.MS + MS*b.TS;
    c.MS= MS*b.MS;
    return c;
}
Phanso operator + (int x, Phanso b){
    Phanso c;
    c.TS= x*b.MS + b.TS;
    c.MS= b.MS;
    return c;
}
int main(){
    Phanso a(1,2), b(3,5);
    Phanso c;
    c= a+b;
    Phanso();  // ket qua a+b
    c= 1+b;
    Phanso(); // ket qua 1+b
}
