import { Admin, Resource } from 'react-admin';
import { UserList, UserEdit, UserCreate, UserShow } from './users';

// Data provider options - switch between them:

// Option 1: Simple REST data provider

import simpleRestProvider from 'ra-data-simple-rest';
const dataProvider = simpleRestProvider('http://127.0.0.1:8080/api');

// Option 2: Custom data provider

//import { customDataProvider } from './dataProvider';
//const dataProvider = customDataProvider;

function App() {
  return (
    <Admin dataProvider={dataProvider} title="React Admin API Test">
      <Resource 
        name="users" 
        list={UserList}
        edit={UserEdit}
        create={UserCreate}
        show={UserShow}
        recordRepresentation="name"
      />
    </Admin>
  );
}

export default App;