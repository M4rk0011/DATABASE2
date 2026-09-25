export interface User {
  id: number;
  name: string;
  position: string;
  department: string;
  details: {
    role: [number, string];
  };
}

