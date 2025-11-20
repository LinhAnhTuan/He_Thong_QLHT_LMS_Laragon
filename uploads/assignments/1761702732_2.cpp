#include<iostream>
#define MAX 100
using namespace std;
class m1
{
private:
    int a[MAX];
    int cot;
    int hang;

public:
    void nhap(int a[][MAX], int cot, int hang);
    void xuat(int a[][MAX], int &cot, int &hang);
    void xoaCot(int a[][MAX], int &cot, int &hang);
    void xoaDong(int a[][MAX], int &cot, int &hang);
};
void m1::nhap(int a[][MAX], int cot, int hang)
{
	for(int i =0; i < hang; i++)
	{
		for(int j = 0; j < cot; j++)
		{
			cout << "Nhap a[" << i << "][" << j << "]: ";
			cin >> a[i][j];
		}
	}
}
void m1::xuat(int a[][MAX], int &cot, int &hang)
{
	for(int i =0; i < hang; i++)
	{
		for(int j = 0; j < cot; j++)
		{
			cout << a[i][j] << "\t";
		}
		cout << "\n";
	}
}
void m1::xoaCot(int a[][MAX], int &cot, int &hang)
{
	int k;
	do{
		cout << "Nhap cot muon xoa: ";
		cin >> k;
	}while(k > cot || k < 0);
	for(int i=0; i<=cot-1; i++){
        for(int j=k-1; j<=cot-1; j++){
            a[i][j]=a[i][j+1];
        }
    }
	cot--;
	cout << "\nSau khi xoa cot " << k << "\n";
	xuat(a,cot, hang);
}
void m1::xoaDong(int a[][MAX], int &cot, int &hang)
{
	int h;
	do{
		cout << "Nhap dong muon xoa: ";
		cin >> h;
	}while(h > hang || h < 0);
	for(int i=0; i<hang; i++){
        for(int j=h; j<hang-1; j++){
            a[j][i]=a[j+1][i];
        }
    }
	hang--;
	cout << "\nSau khi xoa dong " << h << "\n";
	xuat(a,cot,hang);
}
int main()
{
	
	char chon;
	m1 b;
	int a[MAX][MAX], n, x, k,m;
	cout << "Nhap cot phan tu: ";
	cin >> n;
	cout << "Nhap hang phan tu: ";
	cin >> m;
	b.nhap(a,m,n);
	b.xuat(a,m,n);
	b.xoaCot(a,m,n);
	b.xoaDong(a,m,n);
}

