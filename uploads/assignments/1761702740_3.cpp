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
    void sapXepTang(int a[][MAX], int cot, int hang);
    void sapXepGiam(int a[][MAX], int cot, int hang);
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
void m1::sapXepTang(int a[][MAX], int cot, int hang){
	int tg;
    for(int i = 0;i<cot;i++){
        for(int j = 0; j<hang; j++){
            int k,l;
            for(k = i; k<cot; k++){
                int t = 0;
                if(k == i) t = j+1;
                for(l = t; l<hang;l++){
                    if(a[i][j] > a[k][l]){
                        tg = a[i][j];
                        a[i][j] = a[k][l];
                        a[k][l] = tg;
                    }
                }
            }
        }
    }
     
    xuat(a,hang,cot);
}
void m1::sapXepGiam(int a[][MAX], int cot, int hang){
	int tg;
    for(int i = 0;i<cot;i++){
        for(int j = 0; j<hang; j++){
            int k,l;
            for(k = i; k<cot; k++){
                int t = 0;
                if(k == i) t = j+1;
                for(l = t; l<hang;l++){
                    if(a[i][j] < a[k][l]){
                        tg = a[i][j];
                        a[i][j] = a[k][l];
                        a[k][l] = tg;
                    }
                }
            }
        }
    }
     
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
    cout<<"\n Hàm sau khi sap xep tang dan"<<endl;
	b.sapXepTang(a,m,n);
	cout<<"\n Hàm sau khi sap xep giam dan"<<endl;
	b.sapXepGiam(a,m,n);
}

